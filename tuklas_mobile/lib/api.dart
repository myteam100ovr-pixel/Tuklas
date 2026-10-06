import 'dart:convert';
import 'dart:typed_data';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:http/http.dart' as http;

import 'config.dart';

class ApiException implements Exception {
  ApiException(
    this.message, {
    this.twoFactorRequired = false,
    this.fieldErrors = const {},
    this.statusCode,
  });

  final String message;
  final bool twoFactorRequired;
  final Map<String, String> fieldErrors;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiClient {
  ApiClient._();
  static final ApiClient instance = ApiClient._();

  static const _tokenKey = 'tuklas_token';
  final _storage = const FlutterSecureStorage();

  /// The signed-in person. Filled in after login, or when the app starts with a saved token.
  Map<String, dynamic>? user;

  Future<String?> get token => _storage.read(key: _tokenKey);

  Map<String, String> _headers(String? bearer) => {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    if (bearer != null) 'Authorization': 'Bearer $bearer',
  };

  Future<Map<String, dynamic>> _decode(http.Response response) async {
    Map<String, dynamic> body = {};
    if (response.body.isNotEmpty) {
      try {
        final parsed = jsonDecode(response.body);
        if (parsed is Map<String, dynamic>) body = parsed;
      } catch (_) {
        // The server sent something that is not JSON, so fall through to the generic message.
      }
    }

    if (response.statusCode >= 200 && response.statusCode < 300) return body;

    if (response.statusCode == 401) {
      await _storage.delete(key: _tokenKey);
      user = null;
    }

    var message =
        (body['message'] as String?) ??
        'Something went wrong (${response.statusCode}).';
    final fieldErrors = <String, String>{};
    final errors = body['errors'];
    if (errors is Map) {
      errors.forEach((key, value) {
        if (value is List && value.isNotEmpty) {
          fieldErrors['$key'] = '${value.first}';
        }
      });
      if (fieldErrors.isNotEmpty) message = fieldErrors.values.first;
    }

    throw ApiException(
      message,
      twoFactorRequired: body['two_factor_required'] == true,
      fieldErrors: fieldErrors,
      statusCode: response.statusCode,
    );
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? body,
    bool auth = true,
  }) async {
    String? bearer;
    if (auth) {
      bearer = await token;
      if (bearer == null) throw ApiException('Please sign in.');
    }

    final uri = Uri.parse('${AppConfig.apiBase}$path');
    final headers = _headers(bearer);
    if (bearer != null) {
      headers['Authorization'] = 'Bearer $bearer';
    }
    final encoded = body == null ? null : jsonEncode(body);

    late final http.Response response;
    switch (method) {
      case 'GET':
        response = await http
            .get(uri, headers: headers)
            .timeout(const Duration(seconds: 15));
      case 'POST':
        response = await http
            .post(uri, headers: headers, body: encoded)
            .timeout(const Duration(seconds: 15));
      case 'PUT':
        response = await http
            .put(uri, headers: headers, body: encoded)
            .timeout(const Duration(seconds: 15));
      case 'DELETE':
        response = await http
            .delete(uri, headers: headers)
            .timeout(const Duration(seconds: 15));
      default:
        throw ArgumentError('Unsupported method $method');
    }
    return _decode(response);
  }

  Future<Map<String, dynamic>> login(
    String email,
    String password, {
    String? code,
  }) async {
    final body = await _send(
      'POST',
      '/auth/token',
      auth: false,
      body: {
        'email': email,
        'password': password,
        'device_name': 'tuklas-mobile',
        if (code != null && code.isNotEmpty) 'code': code,
      },
    );
    await _storage.write(key: _tokenKey, value: body['token'] as String);
    user = Map<String, dynamic>.from(body['user'] as Map);
    return user!;
  }

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
  }) => _send(
    'POST',
    '/auth/register',
    auth: false,
    body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'device_name': 'tuklas-mobile',
    },
  );

  Future<Map<String, dynamic>> me() async {
    final body = await _send('GET', '/me');
    user = Map<String, dynamic>.from(body['user'] as Map);
    return user!;
  }

  Future<Map<String, dynamic>> dashboard() => _send('GET', '/dashboard');

  Future<String> careerChat(List<Map<String, String>> messages) async {
    final response = await _send(
      'POST',
      '/career-chat',
      body: {'messages': messages},
    );
    return response['reply'] as String;
  }

  Future<Map<String, dynamic>> tesdaCatalog({int page = 1}) =>
      _send('GET', '/tesda?page=$page', auth: false);

  Future<List<dynamic>> documentScans() async {
    final response = await _send('GET', '/document-scans');
    final documents = response['documents'];
    if (documents is! List) {
      throw ApiException('The scan history response was invalid.');
    }
    return documents;
  }

  Future<List<dynamic>> pesoMatches() async {
    final response = await _send('GET', '/peso');
    final matches = response['matches'];
    if (matches is! List) {
      throw ApiException('The PESO matches response was invalid.');
    }
    return matches;
  }

  Future<Map<String, dynamic>> uploadDocument({
    required Uint8List bytes,
    required String filename,
  }) async {
    final bearer = await token;
    if (bearer == null) throw ApiException('Please sign in.');

    final request =
        http.MultipartRequest(
            'POST',
            Uri.parse('${AppConfig.apiBase}/document-scans'),
          )
          ..headers.addAll({
            'Accept': 'application/json',
            'Authorization': 'Bearer $bearer',
          })
          ..files.add(
            http.MultipartFile.fromBytes('file', bytes, filename: filename),
          );
    request.headers['Authorization'] = 'Bearer $bearer';
    final streamedResponse = await request.send().timeout(
      const Duration(seconds: 260),
    );
    final response = await http.Response.fromStream(streamedResponse);
    final body = await _decode(response);
    return Map<String, dynamic>.from(body['document'] as Map);
  }

  Future<Map<String, dynamic>> profile() async {
    if (user?['role'] != 'youth') {
      final response = await _send('GET', '/me');
      final account = Map<String, dynamic>.from(response['user'] as Map);
      user = account;

      return {
        'account': account,
        'youth': null,
        'options': <String, dynamic>{},
      };
    }

    final body = await _send('GET', '/profile');
    final profileUser = Map<String, dynamic>.from(body['user'] as Map);
    final profile = Map<String, dynamic>.from(body['profile'] as Map);
    final account = Map<String, dynamic>.from(user ?? {});
    account.addAll(profileUser);

    return {
      'account': account,
      'youth': {...profile, 'date_of_birth': profileUser['date_of_birth']},
      'options': {
        'education': [
          'Elementary graduate',
          'Junior high school undergraduate',
          'Junior high school graduate',
          'Senior high school undergraduate',
          'Senior high school graduate',
          'Vocational or TVET graduate',
          'College undergraduate',
          'College graduate',
          'Postgraduate',
        ],
        'employment': [
          'Student',
          'Employed',
          'Self-employed',
          'Unemployed',
          'Out of school',
        ],
      },
    };
  }

  Future<Map<String, dynamic>> updateProfile(Map<String, dynamic> data) =>
      _send('PUT', '/profile', body: data);

  Future<void> logout() async {
    final saved = await token;
    if (saved != null) {
      try {
        await _send('DELETE', '/auth/token');
      } catch (_) {
        // Signing out on this device is enough if the server cannot be reached.
      }
    }
    await _storage.delete(key: _tokenKey);
    user = null;
  }
}
