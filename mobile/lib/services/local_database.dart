import 'package:path/path.dart' as p;
import 'package:sqflite/sqflite.dart';
import '../models/local_models.dart';

class LocalDatabase {
  LocalDatabase({this.dbFactory, this.dbPath});

  static final LocalDatabase instance = LocalDatabase();

  final DatabaseFactory? dbFactory;
  final String? dbPath;
  Database? _db;

  Future<Database> get database async {
    if (_db != null && _db!.isOpen) {
      return _db!;
    }
    _db = await _open();
    return _db!;
  }

  Future<Database> _open() async {
    final factory = dbFactory ?? databaseFactory;
    final path = dbPath ?? p.join(await getDatabasesPath(), 'uco_collector_v1.db');

    return factory.openDatabase(
      path,
      options: OpenDatabaseOptions(
        version: 1,
        onCreate: (db, version) async {
          await db.execute('''
            CREATE TABLE local_vendors (
              id INTEGER PRIMARY KEY,
              vendor_code TEXT NOT NULL,
              name TEXT NOT NULL,
              tier TEXT NOT NULL,
              address TEXT,
              gps_lat REAL NOT NULL,
              gps_lng REAL NOT NULL,
              mou_status TEXT NOT NULL,
              qualiplast_tier TEXT
            )
          ''');

          await db.execute('''
            CREATE TABLE local_containers (
              id INTEGER PRIMARY KEY,
              qr_code TEXT NOT NULL UNIQUE,
              qualiplast_tier TEXT NOT NULL,
              capacity_liters REAL NOT NULL,
              assigned_vendor_id INTEGER,
              status TEXT NOT NULL
            )
          ''');

          await db.execute('''
            CREATE TABLE offline_collections (
              client_event_uuid TEXT PRIMARY KEY,
              vendor_id INTEGER NOT NULL,
              vendor_name TEXT NOT NULL,
              vendor_tier TEXT NOT NULL,
              container_id INTEGER NOT NULL,
              container_qr_code TEXT NOT NULL,
              container_tier TEXT NOT NULL,
              scan_method TEXT NOT NULL,
              fallback_reason TEXT,
              collected_at TEXT NOT NULL,
              gps_lat REAL NOT NULL,
              gps_lng REAL NOT NULL,
              gps_accuracy_meters REAL,
              gps_is_mocked INTEGER NOT NULL DEFAULT 0,
              volume_liters REAL NOT NULL,
              field_ffa_percent REAL,
              field_miu_percent REAL,
              field_device_type TEXT NOT NULL,
              payment_amount REAL NOT NULL,
              payment_method TEXT NOT NULL,
              payment_reference TEXT,
              sync_status TEXT NOT NULL DEFAULT 'pending_event',
              sync_error TEXT,
              synced_at TEXT,
              server_event_id INTEGER,
              gps_mismatch_flag INTEGER NOT NULL DEFAULT 0,
              container_mismatch_flag INTEGER NOT NULL DEFAULT 0
            )
          ''');

          await db.execute('''
            CREATE TABLE offline_photos (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              client_event_uuid TEXT NOT NULL,
              photo_type TEXT NOT NULL,
              local_file_path TEXT NOT NULL,
              sha256_hash TEXT NOT NULL,
              captured_at TEXT NOT NULL,
              upload_status TEXT NOT NULL DEFAULT 'pending',
              error_message TEXT
            )
          ''');

          await db.execute('''
            CREATE TABLE sync_meta (
              key TEXT PRIMARY KEY,
              value TEXT
            )
          ''');
        },
      ),
    );
  }

  Future<void> close() async {
    await _db?.close();
    _db = null;
  }

  Future<void> saveMasterData({
    required List<LocalVendor> vendors,
    required List<LocalContainer> containers,
    required String serverTime,
  }) async {
    final db = await database;
    await db.transaction((txn) async {
      await txn.delete('local_vendors');
      for (final v in vendors) {
        await txn.insert('local_vendors', v.toDb(), conflictAlgorithm: ConflictAlgorithm.replace);
      }

      await txn.delete('local_containers');
      for (final c in containers) {
        await txn.insert('local_containers', c.toDb(), conflictAlgorithm: ConflictAlgorithm.replace);
      }

      await txn.insert(
        'sync_meta',
        {'key': 'last_master_sync', 'value': serverTime},
        conflictAlgorithm: ConflictAlgorithm.replace,
      );
    });
  }

  Future<String?> getLastMasterSync() async {
    final db = await database;
    final rows = await db.query('sync_meta', where: 'key = ?', whereArgs: ['last_master_sync']);
    if (rows.isEmpty) return null;
    return rows.first['value'] as String?;
  }

  Future<List<LocalVendor>> getVendors({String search = ''}) async {
    final db = await database;
    final rows = search.trim().isEmpty
        ? await db.query('local_vendors', orderBy: 'name ASC')
        : await db.query(
            'local_vendors',
            where: 'name LIKE ? OR vendor_code LIKE ?',
            whereArgs: ['%${search.trim()}%', '%${search.trim()}%'],
            orderBy: 'name ASC',
          );
    return rows.map(LocalVendor.fromDb).toList();
  }

  Future<List<LocalContainer>> getContainers({int? vendorId}) async {
    final db = await database;
    final rows = vendorId == null
        ? await db.query('local_containers', orderBy: 'qr_code ASC')
        : await db.query(
            'local_containers',
            where: 'assigned_vendor_id = ? OR assigned_vendor_id IS NULL',
            whereArgs: [vendorId],
            orderBy: 'assigned_vendor_id DESC, qr_code ASC',
          );
    return rows.map(LocalContainer.fromDb).toList();
  }

  Future<LocalContainer?> findContainerByQr(String qrCode) async {
    final db = await database;
    final rows = await db.query(
      'local_containers',
      where: 'UPPER(qr_code) = ?',
      whereArgs: [qrCode.trim().toUpperCase()],
      limit: 1,
    );
    if (rows.isEmpty) return null;
    return LocalContainer.fromDb(rows.first);
  }

  Future<void> insertCollectionWithPhotos(
    OfflineCollection collection,
    List<OfflinePhoto> photos,
  ) async {
    if (photos.isEmpty) {
      throw ArgumentError('At least 1 photo is required.');
    }
    final db = await database;
    await db.transaction((txn) async {
      await txn.insert(
        'offline_collections',
        collection.toDb(),
        conflictAlgorithm: ConflictAlgorithm.abort,
      );
      for (final photo in photos) {
        await txn.insert('offline_photos', photo.toDb());
      }
    });
  }

  Future<List<OfflineCollection>> getCollections({bool onlyPending = false}) async {
    final db = await database;
    final rows = onlyPending
        ? await db.query(
            'offline_collections',
            where: "sync_status != 'completed'",
            orderBy: 'collected_at ASC', // FIFO for sync
          )
        : await db.query(
            'offline_collections',
            orderBy: 'collected_at DESC',
          );

    final List<OfflineCollection> result = [];
    for (final row in rows) {
      final uuid = row['client_event_uuid'] as String;
      final photoRows = await db.query(
        'offline_photos',
        where: 'client_event_uuid = ?',
        whereArgs: [uuid],
        orderBy: 'id ASC',
      );
      result.add(
        OfflineCollection.fromDb(
          row,
          photos: photoRows.map(OfflinePhoto.fromDb).toList(),
        ),
      );
    }
    return result;
  }

  Future<OfflineCollection?> getCollection(String clientEventUuid) async {
    final db = await database;
    final rows = await db.query(
      'offline_collections',
      where: 'client_event_uuid = ?',
      whereArgs: [clientEventUuid],
      limit: 1,
    );
    if (rows.isEmpty) return null;

    final photoRows = await db.query(
      'offline_photos',
      where: 'client_event_uuid = ?',
      whereArgs: [clientEventUuid],
      orderBy: 'id ASC',
    );
    return OfflineCollection.fromDb(
      rows.first,
      photos: photoRows.map(OfflinePhoto.fromDb).toList(),
    );
  }

  Future<void> markEventSynced({
    required String clientEventUuid,
    required int serverEventId,
    required String syncedAt,
    required bool gpsMismatchFlag,
    required bool containerMismatchFlag,
  }) async {
    final db = await database;
    await db.update(
      'offline_collections',
      {
        'sync_status': 'event_synced',
        'sync_error': null,
        'server_event_id': serverEventId,
        'synced_at': syncedAt,
        'gps_mismatch_flag': gpsMismatchFlag ? 1 : 0,
        'container_mismatch_flag': containerMismatchFlag ? 1 : 0,
      },
      where: 'client_event_uuid = ?',
      whereArgs: [clientEventUuid],
    );
  }

  Future<void> markCollectionCompleted(String clientEventUuid) async {
    final db = await database;
    await db.update(
      'offline_collections',
      {'sync_status': 'completed', 'sync_error': null},
      where: 'client_event_uuid = ?',
      whereArgs: [clientEventUuid],
    );
  }

  Future<void> markCollectionFailed(String clientEventUuid, String error) async {
    final db = await database;
    await db.update(
      'offline_collections',
      {'sync_status': 'failed', 'sync_error': error},
      where: 'client_event_uuid = ?',
      whereArgs: [clientEventUuid],
    );
  }

  Future<void> markPhotoUploaded(int photoId) async {
    final db = await database;
    await db.update(
      'offline_photos',
      {'upload_status': 'uploaded', 'error_message': null},
      where: 'id = ?',
      whereArgs: [photoId],
    );
  }

  Future<void> markPhotoFailed(int photoId, String error) async {
    final db = await database;
    await db.update(
      'offline_photos',
      {'upload_status': 'failed', 'error_message': error},
      where: 'id = ?',
      whereArgs: [photoId],
    );
  }

  Future<({int pendingEvents, int pendingPhotos, int todayCount, double todayLiters})> getDashboardStats() async {
    final db = await database;
    final pendingEventsRows = await db.rawQuery(
      "SELECT COUNT(*) AS cnt FROM offline_collections WHERE sync_status != 'completed'",
    );
    final pendingPhotoRows = await db.rawQuery(
      "SELECT COUNT(*) AS cnt FROM offline_photos WHERE upload_status != 'uploaded'",
    );

    final todayPrefix = DateTime.now().toUtc().toIso8601String().substring(0, 10);
    final todayRows = await db.rawQuery(
      'SELECT COUNT(*) AS cnt, COALESCE(SUM(volume_liters), 0) AS vol FROM offline_collections WHERE collected_at LIKE ?',
      ['$todayPrefix%'],
    );

    return (
      pendingEvents: (pendingEventsRows.first['cnt'] as int?) ?? 0,
      pendingPhotos: (pendingPhotoRows.first['cnt'] as int?) ?? 0,
      todayCount: (todayRows.first['cnt'] as int?) ?? 0,
      todayLiters: ((todayRows.first['vol'] as num?) ?? 0).toDouble(),
    );
  }
}
