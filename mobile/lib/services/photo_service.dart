import 'dart:io';
import 'package:crypto/crypto.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:image_picker/image_picker.dart';
import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart';

class CapturedPhoto {
  final String localPath;
  final String sha256Hash;
  final String capturedAtIso;

  const CapturedPhoto({
    required this.localPath,
    required this.sha256Hash,
    required this.capturedAtIso,
  });
}

class PhotoService {
  final ImagePicker _picker = ImagePicker();

  /// Live camera only (gallery intentionally disabled for EU 2025/2181 anti-fraud).
  Future<CapturedPhoto?> captureLivePhoto({
    required String clientEventUuid,
    required String photoType,
  }) async {
    final picked = await _picker.pickImage(
      source: ImageSource.camera,
      preferredCameraDevice: CameraDevice.rear,
      imageQuality: 90,
    );
    if (picked == null) return null;

    final docsDir = await getApplicationDocumentsDirectory();
    final photoDir = Directory(p.join(docsDir.path, 'uco_photos'));
    if (!await photoDir.exists()) {
      await photoDir.create(recursive: true);
    }

    final fileName = '${clientEventUuid}_${photoType}_${DateTime.now().millisecondsSinceEpoch}.jpg';
    final targetPath = p.join(photoDir.path, fileName);

    final compressed = await FlutterImageCompress.compressAndGetFile(
      picked.path,
      targetPath,
      minWidth: 1280,
      minHeight: 1280,
      quality: 75,
    );

    final finalFile = File(compressed?.path ?? picked.path);
    if (compressed == null) {
      await finalFile.copy(targetPath);
    }

    final savedBytes = await File(targetPath).readAsBytes();
    final digest = sha256.convert(savedBytes).toString();

    return CapturedPhoto(
      localPath: targetPath,
      sha256Hash: digest,
      capturedAtIso: DateTime.now().toUtc().toIso8601String(),
    );
  }
}
