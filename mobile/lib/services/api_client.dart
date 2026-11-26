import 'package:dio/dio.dart';
import '../core/config.dart';
import '../models/local_models.dart';

class ApiUnauthorizedException implements Exception {
  final String message;
  ApiUnauthorizedException(this.message);

  @override
  String toString() => message;
}

class ApiClient {
  Dio _buildDio(String baseUrl, {String? token}) {
    return Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 30),
        sendTimeout: const Duration(seconds: 60),
        headers: {
          'Accept': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      ),
    );
  }

  Future<Map<String, dynamic>> login({
    required String identifier,
    required String password,
  }) async {
    final baseUrl = await AppConfig.getBaseUrl();
    final dio = _buildDio(baseUrl);
    final response = await dio.post('/auth/login', data: {
      'identifier': identifier,
      'password': password,
      'device_name': 'flutter-collector-app',
    });
    return Map<String, dynamic>.from(response.data as Map);
  }

  Future<void> logout() async {
    final token = await AppConfig.getToken();
    if (token == null) return;
    final baseUrl = await AppConfig.getBaseUrl();
    try {
      await _buildDio(baseUrl, token: token).post('/auth/logout');
    } catch (_) {
      // Ignore network errors when logging out locally.
    }
  }

  Future<({String serverTime, List<LocalVendor> vendors, List<LocalContainer> containers})> fetchMasterData() async {
    final token = await AppConfig.getToken();
    if (token == null) throw ApiUnauthorizedException('Missing auth token.');
    final baseUrl = await AppConfig.getBaseUrl();

    try {
      final res = await _buildDio(baseUrl, token: token).get('/master-data');
      final data = Map<String, dynamic>.from(res.data as Map);
      final vendors = (data['vendors'] as List)
          .map((e) => LocalVendor.fromApi(Map<String, dynamic>.from(e as Map)))
          .toList();
      final containers = (data['containers'] as List)
          .map((e) => LocalContainer.fromApi(Map<String, dynamic>.from(e as Map)))
          .toList();
      return (
        serverTime: data['server_time'] as String,
        vendors: vendors,
        containers: containers,
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 || e.response?.statusCode == 403) {
        throw ApiUnauthorizedException('Account inactive or session expired.');
      }
      rethrow;
    }
  }

  Future<Map<String, dynamic>> syncCollection(OfflineCollection collection) async {
    final token = await AppConfig.getToken();
    if (token == null) throw ApiUnauthorizedException('Missing auth token.');
    final baseUrl = await AppConfig.getBaseUrl();

    try {
      final res = await _buildDio(baseUrl, token: token).post(
        '/collections/sync',
        data: collection.toSyncPayload(),
      );
      return Map<String, dynamic>.from(res.data as Map);
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 || e.response?.statusCode == 403) {
        throw ApiUnauthorizedException('Collector access revoked or expired.');
      }
      rethrow;
    }
  }

  Future<void> uploadPhoto(String clientEventUuid, OfflinePhoto photo) async {
    final token = await AppConfig.getToken();
    if (token == null) throw ApiUnauthorizedException('Missing auth token.');
    final baseUrl = await AppConfig.getBaseUrl();

    final formData = FormData.fromMap({
      'photo_type': photo.photoType,
      'sha256_hash': photo.sha256Hash,
      'captured_at': photo.capturedAt,
      'photo': await MultipartFile.fromFile(photo.localFilePath),
    });

    try {
      await _buildDio(baseUrl, token: token).post(
        '/collections/$clientEventUuid/photos',
        data: formData,
      );
    } on DioException catch (e) {
      if (e.response?.statusCode == 401 || e.response?.statusCode == 403) {
        throw ApiUnauthorizedException('Collector access revoked or expired.');
      }
      rethrow;
    }
  }
}
