class LocalVendor {
  final int id;
  final String vendorCode;
  final String name;
  final String tier;
  final String? address;
  final double gpsLat;
  final double gpsLng;
  final String mouStatus;
  final String? qualiplastTier;

  const LocalVendor({
    required this.id,
    required this.vendorCode,
    required this.name,
    required this.tier,
    this.address,
    required this.gpsLat,
    required this.gpsLng,
    required this.mouStatus,
    this.qualiplastTier,
  });

  factory LocalVendor.fromApi(Map<String, dynamic> json) => LocalVendor(
        id: json['id'] as int,
        vendorCode: json['vendor_code'] as String,
        name: json['name'] as String,
        tier: json['tier'] as String,
        address: json['address'] as String?,
        gpsLat: (json['gps_lat'] as num).toDouble(),
        gpsLng: (json['gps_lng'] as num).toDouble(),
        mouStatus: (json['mou_status'] as String?) ?? 'none',
        qualiplastTier: json['qualiplast_tier'] as String?,
      );

  factory LocalVendor.fromDb(Map<String, dynamic> row) => LocalVendor(
        id: row['id'] as int,
        vendorCode: row['vendor_code'] as String,
        name: row['name'] as String,
        tier: row['tier'] as String,
        address: row['address'] as String?,
        gpsLat: (row['gps_lat'] as num).toDouble(),
        gpsLng: (row['gps_lng'] as num).toDouble(),
        mouStatus: (row['mou_status'] as String?) ?? 'none',
        qualiplastTier: row['qualiplast_tier'] as String?,
      );

  Map<String, dynamic> toDb() => {
        'id': id,
        'vendor_code': vendorCode,
        'name': name,
        'tier': tier,
        'address': address,
        'gps_lat': gpsLat,
        'gps_lng': gpsLng,
        'mou_status': mouStatus,
        'qualiplast_tier': qualiplastTier,
      };
}

class LocalContainer {
  final int id;
  final String qrCode;
  final String qualiplastTier;
  final double capacityLiters;
  final int? assignedVendorId;
  final String status;

  const LocalContainer({
    required this.id,
    required this.qrCode,
    required this.qualiplastTier,
    required this.capacityLiters,
    this.assignedVendorId,
    required this.status,
  });

  factory LocalContainer.fromApi(Map<String, dynamic> json) => LocalContainer(
        id: json['id'] as int,
        qrCode: json['qr_code'] as String,
        qualiplastTier: json['qualiplast_tier'] as String,
        capacityLiters: (json['capacity_liters'] as num).toDouble(),
        assignedVendorId: json['assigned_vendor_id'] as int?,
        status: (json['status'] as String?) ?? 'active',
      );

  factory LocalContainer.fromDb(Map<String, dynamic> row) => LocalContainer(
        id: row['id'] as int,
        qrCode: row['qr_code'] as String,
        qualiplastTier: row['qualiplast_tier'] as String,
        capacityLiters: (row['capacity_liters'] as num).toDouble(),
        assignedVendorId: row['assigned_vendor_id'] as int?,
        status: (row['status'] as String?) ?? 'active',
      );

  Map<String, dynamic> toDb() => {
        'id': id,
        'qr_code': qrCode,
        'qualiplast_tier': qualiplastTier,
        'capacity_liters': capacityLiters,
        'assigned_vendor_id': assignedVendorId,
        'status': status,
      };
}

class OfflinePhoto {
  final int? id;
  final String clientEventUuid;
  final String photoType; // pickup_evidence | ffa_reading
  final String localFilePath;
  final String sha256Hash;
  final String capturedAt;
  final String uploadStatus; // pending | uploaded | failed
  final String? errorMessage;

  const OfflinePhoto({
    this.id,
    required this.clientEventUuid,
    required this.photoType,
    required this.localFilePath,
    required this.sha256Hash,
    required this.capturedAt,
    this.uploadStatus = 'pending',
    this.errorMessage,
  });

  factory OfflinePhoto.fromDb(Map<String, dynamic> row) => OfflinePhoto(
        id: row['id'] as int?,
        clientEventUuid: row['client_event_uuid'] as String,
        photoType: row['photo_type'] as String,
        localFilePath: row['local_file_path'] as String,
        sha256Hash: row['sha256_hash'] as String,
        capturedAt: row['captured_at'] as String,
        uploadStatus: row['upload_status'] as String,
        errorMessage: row['error_message'] as String?,
      );

  Map<String, dynamic> toDb() => {
        if (id != null) 'id': id,
        'client_event_uuid': clientEventUuid,
        'photo_type': photoType,
        'local_file_path': localFilePath,
        'sha256_hash': sha256Hash,
        'captured_at': capturedAt,
        'upload_status': uploadStatus,
        'error_message': errorMessage,
      };
}

class OfflineCollection {
  final String clientEventUuid;
  final int vendorId;
  final String vendorName;
  final String vendorTier;
  final int containerId;
  final String containerQrCode;
  final String containerTier;
  final String scanMethod; // qr_scan | manual_fallback
  final String? fallbackReason;
  final String collectedAt;
  final double gpsLat;
  final double gpsLng;
  final double? gpsAccuracyMeters;
  final bool gpsIsMocked;
  final double volumeLiters;
  final double? fieldFfaPercent;
  final double? fieldMiuPercent;
  final String fieldDeviceType; // colorimetric_kit | testo | ebro
  final double paymentAmount;
  final String paymentMethod; // cash | momo | bank | credit
  final String? paymentReference;
  final String syncStatus; // pending_event | event_synced | completed | failed
  final String? syncError;
  final String? syncedAt;
  final int? serverEventId;
  final bool gpsMismatchFlag;
  final bool containerMismatchFlag;
  final List<OfflinePhoto> photos;

  const OfflineCollection({
    required this.clientEventUuid,
    required this.vendorId,
    required this.vendorName,
    required this.vendorTier,
    required this.containerId,
    required this.containerQrCode,
    required this.containerTier,
    required this.scanMethod,
    this.fallbackReason,
    required this.collectedAt,
    required this.gpsLat,
    required this.gpsLng,
    this.gpsAccuracyMeters,
    this.gpsIsMocked = false,
    required this.volumeLiters,
    this.fieldFfaPercent,
    this.fieldMiuPercent,
    required this.fieldDeviceType,
    required this.paymentAmount,
    required this.paymentMethod,
    this.paymentReference,
    this.syncStatus = 'pending_event',
    this.syncError,
    this.syncedAt,
    this.serverEventId,
    this.gpsMismatchFlag = false,
    this.containerMismatchFlag = false,
    this.photos = const [],
  });

  factory OfflineCollection.fromDb(
    Map<String, dynamic> row, {
    List<OfflinePhoto> photos = const [],
  }) =>
      OfflineCollection(
        clientEventUuid: row['client_event_uuid'] as String,
        vendorId: row['vendor_id'] as int,
        vendorName: row['vendor_name'] as String,
        vendorTier: row['vendor_tier'] as String,
        containerId: row['container_id'] as int,
        containerQrCode: row['container_qr_code'] as String,
        containerTier: row['container_tier'] as String,
        scanMethod: row['scan_method'] as String,
        fallbackReason: row['fallback_reason'] as String?,
        collectedAt: row['collected_at'] as String,
        gpsLat: (row['gps_lat'] as num).toDouble(),
        gpsLng: (row['gps_lng'] as num).toDouble(),
        gpsAccuracyMeters: (row['gps_accuracy_meters'] as num?)?.toDouble(),
        gpsIsMocked: (row['gps_is_mocked'] as int? ?? 0) == 1,
        volumeLiters: (row['volume_liters'] as num).toDouble(),
        fieldFfaPercent: (row['field_ffa_percent'] as num?)?.toDouble(),
        fieldMiuPercent: (row['field_miu_percent'] as num?)?.toDouble(),
        fieldDeviceType: row['field_device_type'] as String,
        paymentAmount: (row['payment_amount'] as num).toDouble(),
        paymentMethod: row['payment_method'] as String,
        paymentReference: row['payment_reference'] as String?,
        syncStatus: row['sync_status'] as String,
        syncError: row['sync_error'] as String?,
        syncedAt: row['synced_at'] as String?,
        serverEventId: row['server_event_id'] as int?,
        gpsMismatchFlag: (row['gps_mismatch_flag'] as int? ?? 0) == 1,
        containerMismatchFlag: (row['container_mismatch_flag'] as int? ?? 0) == 1,
        photos: photos,
      );

  Map<String, dynamic> toDb() => {
        'client_event_uuid': clientEventUuid,
        'vendor_id': vendorId,
        'vendor_name': vendorName,
        'vendor_tier': vendorTier,
        'container_id': containerId,
        'container_qr_code': containerQrCode,
        'container_tier': containerTier,
        'scan_method': scanMethod,
        'fallback_reason': fallbackReason,
        'collected_at': collectedAt,
        'gps_lat': gpsLat,
        'gps_lng': gpsLng,
        'gps_accuracy_meters': gpsAccuracyMeters,
        'gps_is_mocked': gpsIsMocked ? 1 : 0,
        'volume_liters': volumeLiters,
        'field_ffa_percent': fieldFfaPercent,
        'field_miu_percent': fieldMiuPercent,
        'field_device_type': fieldDeviceType,
        'payment_amount': paymentAmount,
        'payment_method': paymentMethod,
        'payment_reference': paymentReference,
        'sync_status': syncStatus,
        'sync_error': syncError,
        'synced_at': syncedAt,
        'server_event_id': serverEventId,
        'gps_mismatch_flag': gpsMismatchFlag ? 1 : 0,
        'container_mismatch_flag': containerMismatchFlag ? 1 : 0,
      };

  Map<String, dynamic> toSyncPayload() => {
        'client_event_uuid': clientEventUuid,
        'vendor_id': vendorId,
        'container_id': containerId,
        'container_qr_code': containerQrCode,
        'scan_method': scanMethod,
        'fallback_reason': fallbackReason,
        'collected_at': collectedAt,
        'gps_lat': gpsLat,
        'gps_lng': gpsLng,
        'gps_accuracy_meters': gpsAccuracyMeters,
        'gps_is_mocked': gpsIsMocked,
        'volume_liters': volumeLiters,
        'field_ffa_percent': fieldFfaPercent,
        'field_miu_percent': fieldMiuPercent,
        'field_device_type': fieldDeviceType,
        'payment_amount': paymentAmount,
        'payment_method': paymentMethod,
        'payment_reference': paymentReference,
      };
}
