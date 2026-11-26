import 'dart:io';
import 'package:flutter_test/flutter_test.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';
import 'package:uco_collector/models/local_models.dart';
import 'package:uco_collector/services/api_client.dart';
import 'package:uco_collector/services/local_database.dart';
import 'package:uco_collector/services/sync_engine.dart';

class _FakeApiClient extends ApiClient {
  int syncCalls = 0;
  int photoCalls = 0;
  bool failNextPhoto = false;
  bool throwUnauthorizedOnSync = false;

  @override
  Future<Map<String, dynamic>> syncCollection(OfflineCollection collection) async {
    syncCalls++;
    if (throwUnauthorizedOnSync) {
      throw ApiUnauthorizedException('Collector deactivated');
    }
    return {
      'status': 'created',
      'event': {
        'id': 101,
        'client_event_uuid': collection.clientEventUuid,
        'synced_at': '2026-11-26T18:00:00Z',
        'gps_mismatch_flag': false,
        'container_mismatch_flag': false,
      },
    };
  }

  @override
  Future<void> uploadPhoto(String clientEventUuid, OfflinePhoto photo) async {
    photoCalls++;
    if (failNextPhoto) {
      failNextPhoto = false;
      throw Exception('Simulated 3G drop during photo upload');
    }
  }
}

void main() {
  sqfliteFfiInit();

  late LocalDatabase db;
  late Directory tempDir;

  setUp(() async {
    tempDir = await Directory.systemTemp.createTemp('uco_test_');
    db = LocalDatabase(dbFactory: databaseFactoryFfi, dbPath: inMemoryDatabasePath);
    await db.database;
  });

  tearDown(() async {
    await db.close();
    if (await tempDir.exists()) {
      await tempDir.delete(recursive: true);
    }
  });

  test('offline database caches master data, enforces min 1 photo, and handles 2-step partial sync retry', () async {
    await db.saveMasterData(
      serverTime: '2026-11-26T18:00:00Z',
      vendors: const [
        LocalVendor(
          id: 1,
          vendorCode: 'VND-001',
          name: 'Osu Kitchen',
          tier: 'restaurant',
          gpsLat: 5.556,
          gpsLng: -0.182,
          mouStatus: 'mou_active',
        ),
      ],
      containers: const [
        LocalContainer(
          id: 10,
          qrCode: 'QP-20L-001',
          qualiplastTier: '20L Jerrycan',
          capacityLiters: 20,
          assignedVendorId: 1,
          status: 'active',
        ),
      ],
    );

    final matched = await db.findContainerByQr('qp-20l-001');
    expect(matched?.id, 10);

    const collection = OfflineCollection(
      clientEventUuid: '11111111-1111-4111-8111-111111111111',
      vendorId: 1,
      vendorName: 'Osu Kitchen',
      vendorTier: 'restaurant',
      containerId: 10,
      containerQrCode: 'QP-20L-001',
      containerTier: '20L Jerrycan',
      scanMethod: 'qr_scan',
      collectedAt: '2026-11-26T17:50:00Z',
      gpsLat: 5.556,
      gpsLng: -0.182,
      volumeLiters: 20.0,
      fieldDeviceType: 'testo',
      paymentAmount: 150.0,
      paymentMethod: 'momo',
    );

    // Enforces minimum 1 photo
    expect(() => db.insertCollectionWithPhotos(collection, const []), throwsArgumentError);

    final imgFile = File('${tempDir.path}/pickup.jpg')..writeAsBytesSync([1, 2, 3, 4]);
    await db.insertCollectionWithPhotos(collection, [
      OfflinePhoto(
        clientEventUuid: collection.clientEventUuid,
        photoType: 'pickup_evidence',
        localFilePath: imgFile.path,
        sha256Hash: 'a' * 64,
        capturedAt: '2026-11-26T17:50:00Z',
      ),
    ]);

    final fakeApi = _FakeApiClient()..failNextPhoto = true;
    final engine = SyncEngine(db: db, api: fakeApi);

    // Run 1: Event succeeds, photo fails -> stays in queue as failed with event_synced!
    final run1 = await engine.syncAllPending();
    expect(run1.syncedEvents, 0);
    expect(run1.failedEvents, 1);
    expect(fakeApi.syncCalls, 1);
    expect(fakeApi.photoCalls, 1);

    // Run 2: Retry -> skips duplicate event POST (serverEventId already saved) and uploads remaining photo!
    final run2 = await engine.syncAllPending();
    expect(run2.syncedEvents, 1);
    expect(run2.failedEvents, 0);
    expect(fakeApi.syncCalls, 1);
    expect(fakeApi.photoCalls, 2);

    final saved = await db.getCollection(collection.clientEventUuid);
    expect(saved?.syncStatus, 'completed');
    expect(saved?.serverEventId, 101);
    expect(saved?.photos.first.uploadStatus, 'uploaded');
  });

  test('unauthorized error pauses sync queue without deleting local unsynced records', () async {
    final imgFile = File('${tempDir.path}/pickup2.jpg')..writeAsBytesSync([9, 8, 7]);
    const uuid = '22222222-2222-4222-8222-222222222222';

    await db.insertCollectionWithPhotos(
      const OfflineCollection(
        clientEventUuid: uuid,
        vendorId: 1,
        vendorName: 'Labadi Stall',
        vendorTier: 'street',
        containerId: 10,
        containerQrCode: 'QP-20L-002',
        containerTier: '20L Jerrycan',
        scanMethod: 'manual_fallback',
        fallbackReason: 'Oily QR label',
        collectedAt: '2026-11-26T17:55:00Z',
        gpsLat: 5.55,
        gpsLng: -0.18,
        volumeLiters: 15.0,
        fieldDeviceType: 'colorimetric_kit',
        paymentAmount: 100.0,
        paymentMethod: 'cash',
      ),
      [
        OfflinePhoto(
          clientEventUuid: uuid,
          photoType: 'pickup_evidence',
          localFilePath: imgFile.path,
          sha256Hash: 'b' * 64,
          capturedAt: '2026-11-26T17:55:00Z',
        ),
      ],
    );

    final fakeApi = _FakeApiClient()..throwUnauthorizedOnSync = true;
    final engine = SyncEngine(db: db, api: fakeApi);

    final res = await engine.syncAllPending();
    expect(res.authError, isNotNull);

    // Record is still safely preserved locally!
    final preserved = await db.getCollection(uuid);
    expect(preserved, isNotNull);
    expect(preserved?.syncStatus, 'pending_event');
  });
}
