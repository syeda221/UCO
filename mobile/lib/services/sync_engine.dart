import 'dart:async';
import 'dart:io';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:workmanager/workmanager.dart';
import '../core/config.dart';
import 'api_client.dart';
import 'local_database.dart';

const String ucoBackgroundSyncTask = 'uco_periodic_offline_sync';

@pragma('vm:entry-point')
void callbackDispatcher() {
  Workmanager().executeTask((task, inputData) async {
    if (task == ucoBackgroundSyncTask) {
      final token = await AppConfig.getToken();
      if (token == null) return true;
      await SyncEngine.instance.syncAllPending();
    }
    return true;
  });
}

class SyncResult {
  final int syncedEvents;
  final int failedEvents;
  final String? authError;

  const SyncResult({
    required this.syncedEvents,
    required this.failedEvents,
    this.authError,
  });
}

class SyncEngine extends ChangeNotifier {
  SyncEngine({LocalDatabase? db, ApiClient? api})
      : _db = db ?? LocalDatabase.instance,
        _api = api ?? ApiClient();

  static final SyncEngine instance = SyncEngine();

  final LocalDatabase _db;
  final ApiClient _api;

  bool _isSyncing = false;
  bool _isOnline = true;
  String? _authBlockedMessage;
  StreamSubscription<List<ConnectivityResult>>? _connSub;

  bool get isSyncing => _isSyncing;
  bool get isOnline => _isOnline;
  String? get authBlockedMessage => _authBlockedMessage;

  Future<void> initMonitoring() async {
    final initial = await Connectivity().checkConnectivity();
    _isOnline = !initial.contains(ConnectivityResult.none);
    notifyListeners();

    _connSub?.cancel();
    _connSub = Connectivity().onConnectivityChanged.listen((results) {
      final online = !results.contains(ConnectivityResult.none);
      final cameBackOnline = !_isOnline && online;
      _isOnline = online;
      notifyListeners();

      if (cameBackOnline) {
        syncAllPending();
      }
    });
  }

  Future<void> registerBackgroundWorker() async {
    await Workmanager().initialize(callbackDispatcher, isInDebugMode: kDebugMode);
    await Workmanager().registerPeriodicTask(
      'uco_sync_worker_15m',
      ucoBackgroundSyncTask,
      frequency: const Duration(minutes: 15),
      constraints: Constraints(networkType: NetworkType.connected),
      existingWorkPolicy: ExistingWorkPolicy.keep,
    );
  }

  Future<bool> refreshMasterData() async {
    try {
      final master = await _api.fetchMasterData();
      await _db.saveMasterData(
        vendors: master.vendors,
        containers: master.containers,
        serverTime: master.serverTime,
      );
      _authBlockedMessage = null;
      notifyListeners();
      return true;
    } on ApiUnauthorizedException catch (e) {
      _authBlockedMessage = e.message;
      notifyListeners();
      return false;
    } catch (_) {
      return false;
    }
  }

  Future<SyncResult> syncAllPending() async {
    if (_isSyncing) {
      return const SyncResult(syncedEvents: 0, failedEvents: 0);
    }

    _isSyncing = true;
    notifyListeners();

    int synced = 0;
    int failed = 0;
    String? authError;

    try {
      final pending = await _db.getCollections(onlyPending: true);

      for (final item in pending) {
        try {
          // Step 1: Sync JSON payload only if not already synced to server.
          if (item.serverEventId == null &&
              (item.syncStatus == 'pending_event' || item.syncStatus == 'failed')) {
            final res = await _api.syncCollection(item);
            final eventData = Map<String, dynamic>.from(res['event'] as Map);
            await _db.markEventSynced(
              clientEventUuid: item.clientEventUuid,
              serverEventId: eventData['id'] as int,
              syncedAt: (eventData['synced_at'] as String?) ?? DateTime.now().toUtc().toIso8601String(),
              gpsMismatchFlag: (eventData['gps_mismatch_flag'] as bool?) ?? false,
              containerMismatchFlag: (eventData['container_mismatch_flag'] as bool?) ?? false,
            );
          }

          // Step 2: Upload each pending/failed photo individually.
          bool allPhotosUploaded = true;
          for (final photo in item.photos) {
            if (photo.uploadStatus == 'uploaded') continue;

            final file = File(photo.localFilePath);
            if (!await file.exists()) {
              allPhotosUploaded = false;
              if (photo.id != null) {
                await _db.markPhotoFailed(photo.id!, 'Local photo file missing.');
              }
              continue;
            }

            try {
              await _api.uploadPhoto(item.clientEventUuid, photo);
              if (photo.id != null) {
                await _db.markPhotoUploaded(photo.id!);
              }
            } on ApiUnauthorizedException {
              rethrow;
            } catch (e) {
              allPhotosUploaded = false;
              if (photo.id != null) {
                await _db.markPhotoFailed(photo.id!, _friendlyError(e));
              }
            }
          }

          if (allPhotosUploaded) {
            await _db.markCollectionCompleted(item.clientEventUuid);
            synced++;
          } else {
            await _db.markCollectionFailed(item.clientEventUuid, 'One or more photos failed to upload.');
            failed++;
          }
        } on ApiUnauthorizedException catch (e) {
          // Pause queue safely without deleting local unsynced records.
          authError = e.message;
          _authBlockedMessage = e.message;
          break;
        } catch (e) {
          await _db.markCollectionFailed(item.clientEventUuid, _friendlyError(e));
          failed++;
        }
      }
    } finally {
      _isSyncing = false;
      notifyListeners();
    }

    return SyncResult(syncedEvents: synced, failedEvents: failed, authError: authError);
  }

  String _friendlyError(Object e) {
    if (e is DioException) {
      final msg = e.response?.data is Map ? (e.response?.data['message']?.toString()) : null;
      return msg ?? e.message ?? 'Network error';
    }
    return e.toString();
  }

  @override
  void dispose() {
    _connSub?.cancel();
    super.dispose();
  }
}
