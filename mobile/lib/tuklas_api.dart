import 'dart:io' show Platform;
import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:file_picker/file_picker.dart';

class TuklasApi {
  TuklasApi({
    FlutterSecureStorage? storage,
    Dio? dio,
    String? baseUrl,
  }) : _storage = storage ?? const FlutterSecureStorage() {
    _dio = dio ?? Dio();
    _dio.options = BaseOptions(
      baseUrl: baseUrl ?? _defaultBaseUrl,
      connectTimeout: const Duration(seconds: 12),
      receiveTimeout: const Duration(seconds: 100),
      sendTimeout: const Duration(seconds: 100),
      headers: const {'Accept': 'application/json'},
    );
    _dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) {
        final token = _token;
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        handler.next(options);
      },
    ));
  }

  static const _tokenKey = 'tuklas.mobile-token';
  static const _baseUrlKey = 'tuklas.api-base-url';
  static const _compiledBaseUrl = String.fromEnvironment('TUKLAS_API_URL');

  final FlutterSecureStorage _storage;
  late final Dio _dio;
  String? _token;

  String get baseUrl => _dio.options.baseUrl;
  String get siteBaseUrl => baseUrl.replaceFirst(RegExp(r'/api/mobile/?$'), '');
  bool get isAuthenticated => _token != null;

  String get _defaultBaseUrl {
    if (_compiledBaseUrl.isNotEmpty) {
      return _mobileBase(_compiledBaseUrl);
    }
    if (!kIsWeb && Platform.isAndroid) {
      return 'http://10.0.2.2:8000/api/mobile';
    }
    return 'http://127.0.0.1:8000/api/mobile';
  }

  Future<void> initialize() async {
    _token = await _storage.read(key: _tokenKey);
    final savedBaseUrl = await _storage.read(key: _baseUrlKey);
    if (savedBaseUrl != null && savedBaseUrl.isNotEmpty) {
      _dio.options.baseUrl = _mobileBase(savedBaseUrl);
    }
  }

  Future<void> setServerAddress(String address) async {
    final uri = Uri.tryParse(address.trim());
    if (uri == null ||
        !uri.hasAuthority ||
        !const ['http', 'https'].contains(uri.scheme)) {
      throw const FormatException('Enter a valid http or https server address.');
    }
    final normalized = _mobileBase(uri.toString());
    _dio.options.baseUrl = normalized;
    await _storage.write(key: _baseUrlKey, value: uri.toString());
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    required String deviceName,
    String? code,
    String? recoveryCode,
  }) async {
    final data = <String, dynamic>{
      'email': email,
      'password': password,
      'device_name': deviceName,
    };
    if (code case final value?) data['code'] = value;
    if (recoveryCode case final value?) data['recovery_code'] = value;

    final response = await _dio.post<Map<String, dynamic>>(
      '/auth/token',
      data: data,
    );
    await _saveResponseToken(response.data);
    return _map(response.data);
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String deviceName,
    required bool acceptedTerms,
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/auth/register',
      data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': password,
        'device_name': deviceName,
        'terms': acceptedTerms,
      },
    );
    await _saveResponseToken(response.data);
    return _map(response.data);
  }

  Future<void> requestPasswordReset(String email) async {
    await _dio.post('/auth/forgot-password', data: {'email': email});
  }

  Uri socialLoginUrl(String provider) =>
      Uri.parse('$siteBaseUrl/auth/$provider/redirect?mobile=1');

  Future<Map<String, dynamic>> exchangeSocialTicket({
    required String ticket,
    required String deviceName,
    String? code,
    String? recoveryCode,
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/auth/social/exchange',
      data: {
        'ticket': ticket,
        'device_name': deviceName,
        if (code case final value?) 'code': value,
        if (recoveryCode case final value?) 'recovery_code': value,
      },
    );
    await _saveResponseToken(response.data);
    return _map(response.data);
  }

  Future<void> resetPassword({
    required String email,
    required String token,
    required String password,
  }) async {
    await _dio.post('/auth/reset-password', data: {
      'email': email,
      'token': token,
      'password': password,
      'password_confirmation': password,
    });
  }

  Future<Map<String, dynamic>> currentUser() async {
    final response = await _dio.get<Map<String, dynamic>>('/me');
    return _map(response.data?['user']);
  }

  Future<void> logout() async {
    try {
      if (_token != null) {
        await _dio.delete('/auth/token');
      }
    } finally {
      _token = null;
      await _storage.delete(key: _tokenKey);
    }
  }

  Future<void> resendVerification() async {
    await _dio.post('/email/verification-notification');
  }

  Future<Map<String, dynamic>> dashboard() async {
    final response = await _dio.get<Map<String, dynamic>>('/dashboard');
    return _map(response.data);
  }

  Future<Map<String, dynamic>> profile() async {
    final response = await _dio.get<Map<String, dynamic>>('/profile');
    return _map(response.data);
  }

  Future<Map<String, dynamic>> saveYouthProfile(Map<String, dynamic> values) async {
    final response = await _dio.put<Map<String, dynamic>>('/profile', data: values);
    return _map(response.data);
  }

  Future<Map<String, dynamic>> saveAccount({
    required String name,
    required String email,
  }) async {
    final response = await _dio.put<Map<String, dynamic>>('/account', data: {
      'name': name,
      'email': email,
    });
    return _map(response.data?['user']);
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    await _dio.put('/password', data: {
      'current_password': currentPassword,
      'password': newPassword,
      'password_confirmation': newPassword,
    });
  }

  Future<Map<String, dynamic>> security() async {
    final response = await _dio.get<Map<String, dynamic>>('/security');
    return _map(response.data);
  }

  Future<Map<String, dynamic>> enableTwoFactor(String currentPassword) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/security/two-factor',
      data: {'current_password': currentPassword},
    );
    return _map(response.data);
  }

  Future<Map<String, dynamic>> confirmTwoFactor(String code) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/security/two-factor/confirm',
      data: {'code': code},
    );
    return _map(response.data);
  }

  Future<List<dynamic>> regenerateRecoveryCodes(String currentPassword) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/security/two-factor/recovery-codes',
      data: {'current_password': currentPassword},
    );
    return List<dynamic>.from(response.data?['recovery_codes'] ?? const []);
  }

  Future<void> disableTwoFactor(String currentPassword) async {
    await _dio.delete('/security/two-factor', data: {
      'current_password': currentPassword,
    });
  }

  Future<Map<String, dynamic>> catalog({int page = 1}) async {
    final response = await _dio.get<Map<String, dynamic>>(
      '/tesda',
      queryParameters: {'page': page},
    );
    return _map(response.data);
  }

  Future<Map<String, dynamic>> scans() async {
    final response = await _dio.get<Map<String, dynamic>>('/document-scans');
    return _map(response.data);
  }

  Future<Map<String, dynamic>> uploadScan({
    required String documentType,
    required PlatformFile file,
    required Uint8List bytes,
    ProgressCallback? onSendProgress,
  }) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/document-scans',
      data: FormData.fromMap({
        'document_type': documentType,
        'file': MultipartFile.fromBytes(bytes, filename: file.name),
      }),
      onSendProgress: onSendProgress,
    );
    return _map(response.data?['document']);
  }

  Future<String> sendChat(List<Map<String, String>> messages) async {
    final response = await _dio.post<Map<String, dynamic>>(
      '/career-chat',
      data: {'messages': messages},
    );
    return '${response.data?['reply'] ?? ''}';
  }

  static bool needsTwoFactor(Object error) =>
      error is DioException &&
      error.response?.statusCode == 409 &&
      error.response?.data is Map &&
      (error.response!.data as Map)['two_factor_required'] == true;

  static String errorMessage(Object error) {
    if (error is FormatException) return error.message;
    if (error is DioException) {
      final data = error.response?.data;
      if (data is Map) {
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final first = errors.values.first;
          if (first is List && first.isNotEmpty) return '${first.first}';
        }
        if (data['message'] is String) return data['message'] as String;
      }
      if (error.response == null) {
        return 'Could not reach Tuklas. Check your connection and server address.';
      }
    }
    return 'Something went wrong. Please try again.';
  }

  Future<void> _saveResponseToken(Map<String, dynamic>? data) async {
    final token = data?['token'];
    if (token is String && token.isNotEmpty) {
      _token = token;
      await _storage.write(key: _tokenKey, value: token);
    }
  }

  static Map<String, dynamic> _map(Object? value) =>
      value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

  static String _mobileBase(String input) {
    final trimmed = input.trim().replaceFirst(RegExp(r'/+$'), '');
    return trimmed.endsWith('/api/mobile') ? trimmed : '$trimmed/api/mobile';
  }
}
