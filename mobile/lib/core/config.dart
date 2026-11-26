import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class AppConfig {
  static const String defaultBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );

  static const _storage = FlutterSecureStorage();
  static const _keyBaseUrl = 'api_base_url';
  static const _keyToken = 'auth_token';
  static const _keyCollectorId = 'collector_id';
  static const _keyCollectorName = 'collector_name';
  static const _keyCollectorPhone = 'collector_phone';

  static Future<String> getBaseUrl() async {
    return (await _storage.read(key: _keyBaseUrl)) ?? defaultBaseUrl;
  }

  static Future<void> setBaseUrl(String url) async {
    final normalized = url.trim().replaceAll(RegExp(r'/+$'), '');
    await _storage.write(key: _keyBaseUrl, value: normalized);
  }

  static Future<void> saveSession({
    required String token,
    required int collectorId,
    required String name,
    required String phone,
  }) async {
    await _storage.write(key: _keyToken, value: token);
    await _storage.write(key: _keyCollectorId, value: collectorId.toString());
    await _storage.write(key: _keyCollectorName, value: name);
    await _storage.write(key: _keyCollectorPhone, value: phone);
  }

  static Future<String?> getToken() => _storage.read(key: _keyToken);
  static Future<String?> getCollectorName() => _storage.read(key: _keyCollectorName);
  static Future<String?> getCollectorPhone() => _storage.read(key: _keyCollectorPhone);
  static Future<int?> getCollectorId() async {
    final raw = await _storage.read(key: _keyCollectorId);
    return raw != null ? int.tryParse(raw) : null;
  }

  static Future<void> clearSession() async {
    await _storage.delete(key: _keyToken);
    await _storage.delete(key: _keyCollectorId);
    await _storage.delete(key: _keyCollectorName);
    await _storage.delete(key: _keyCollectorPhone);
  }
}
