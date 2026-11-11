import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:file_picker/file_picker.dart';
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

  Uri _apiUri(String path) {
    final base = AppConfig.normalizedApiBase;
    final endpoint = path.startsWith('/') ? path : '/$path';
    return Uri.parse('$base$endpoint');
  }

  Future<T> _withConnectionHandling<T>(
    Future<T> Function() request, {
    Duration timeout = const Duration(seconds: 30),
  }) async {
    try {
      return await request().timeout(timeout);
    } on TimeoutException {
      throw ApiException('Tuklas took too long to respond. Please retry.');
    } on HandshakeException {
      throw ApiException(
        'The secure connection to Tuklas failed. Check the app update and your phone’s date and time.',
      );
    } on SocketException {
      throw ApiException(
        'Could not reach Tuklas. Check your internet connection and try again.',
      );
    } on HttpException {
      throw ApiException(
        'Could not complete the connection to Tuklas. Check your internet connection and try again.',
      );
    } on http.ClientException {
      throw ApiException(
        'Could not connect to Tuklas. Check your internet connection and try again.',
      );
    }
  }

  String _httpErrorMessage(int statusCode) => switch (statusCode) {
    401 => 'Your Tuklas session expired. Sign in again.',
    403 => 'Your account does not have access to this feature.',
    413 => 'The selected document is too large for the server. Choose a smaller file.',
    419 => 'Your Tuklas session expired. Sign in again.',
    429 => 'Tuklas is receiving too many requests. Wait a moment, then retry.',
    500 => 'Tuklas encountered a server error. Please retry shortly.',
    502 || 503 || 504 => 'Tuklas is temporarily unavailable. Check your connection and retry shortly.',
    _ => 'Tuklas could not complete the request ($statusCode).',
  };

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

    if (response.statusCode == 401 || response.statusCode == 419) {
      await _storage.delete(key: _tokenKey);
      user = null;
    }

    var message = response.statusCode == 401 || response.statusCode == 419
        ? _httpErrorMessage(response.statusCode)
        : body['message'] is String
        ? body['message'] as String
        : _httpErrorMessage(response.statusCode);
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

    final uri = _apiUri(path);
    final headers = _headers(bearer);
    final encoded = body == null ? null : jsonEncode(body);

    final requestTimeout = path.startsWith('/career-chat')
        ? const Duration(seconds: 120)
        : const Duration(seconds: 30);
    final response = await _withConnectionHandling(() async {
      switch (method) {
        case 'GET':
          return http.get(uri, headers: headers);
        case 'POST':
          return http.post(uri, headers: headers, body: encoded);
        case 'PUT':
          return http.put(uri, headers: headers, body: encoded);
        case 'DELETE':
          return http.delete(uri, headers: headers);
        default:
          throw ArgumentError('Unsupported method $method');
      }
    }, timeout: requestTimeout);
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
        if (code != null && code.isNotEmpty)
          (RegExp(r'^\d{6}$').hasMatch(code) ? 'code' : 'recovery_code'): code,
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
    required bool acceptedTerms,
  }) => _send(
    'POST',
    '/auth/register',
    auth: false,
    body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'terms': acceptedTerms,
      'device_name': 'tuklas-mobile',
    },
  );

  Future<Map<String, dynamic>> me() async {
    final body = await _send('GET', '/me');
    user = Map<String, dynamic>.from(body['user'] as Map);
    return user!;
  }

  Future<Map<String, dynamic>> workspace(
    String path, {
    String method = 'GET',
    Map<String, dynamic>? data,
    bool auth = true,
  }) => _send(method, path, body: data, auth: auth);

  Future<Map<String, dynamic>> dashboard() => _send('GET', '/dashboard');

  Future<String> legalDocument(String document) async {
    final result = await _send('GET', '/legal/$document', auth: false);
    return result['content'] as String? ?? '';
  }

  Future<Map<String, dynamic>> careerChat(
    List<Map<String, String>> messages,
  ) async {
    final response = await _send(
      'POST',
      '/career-chat',
      body: {'messages': messages},
    );
    if (response['reply'] is! String) {
      throw ApiException('The career assistant returned an invalid reply.');
    }

    return {
      'reply': response['reply'] as String,
      'sources': response['sources'] is List ? response['sources'] as List : [],
    };
  }

  Future<Map<String, dynamic>> tesdaCatalog({
    int page = 1,
    int registeredPage = 1,
  }) => _send(
    'GET',
    '/tesda?page=$page&registered_page=$registeredPage',
    auth: false,
  );

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
    String goal = '',
  }) async {
    final bearer = await token;
    if (bearer == null) throw ApiException('Please sign in.');

    final request = http.MultipartRequest('POST', _apiUri('/document-scans'))
      ..headers.addAll({
        'Accept': 'application/json',
        'Authorization': 'Bearer $bearer',
      })
      ..files.add(
        http.MultipartFile.fromBytes('file', bytes, filename: filename),
      );
    if (goal.trim().isNotEmpty) {
      request.fields['goal'] = goal.trim();
    }
    final response = await _withConnectionHandling(() async {
      final streamedResponse = await request.send();
      return http.Response.fromStream(streamedResponse);
    }, timeout: const Duration(seconds: 260));
    final body = await _decode(response);
    return Map<String, dynamic>.from(body['document'] as Map);
  }

  Future<Map<String, dynamic>> submitScholarshipApplication({
    required int scholarshipId,
    required String message,
    required List<PlatformFile> documents,
  }) async {
    final bearer = await token;
    if (bearer == null) throw ApiException('Please sign in.');

    final request =
        http.MultipartRequest(
            'POST',
            _apiUri('/scholarships/$scholarshipId/applications'),
          )
          ..headers.addAll({
            'Accept': 'application/json',
            'Authorization': 'Bearer $bearer',
          })
          ..fields['application_message'] = message;
    for (final document in documents) {
      final bytes = document.bytes;
      if (bytes == null) {
        throw ApiException('Could not read ${document.name}. Choose it again.');
      }
      request.files.add(
        http.MultipartFile.fromBytes(
          'documents[]',
          bytes,
          filename: document.name,
        ),
      );
    }

    final response = await _withConnectionHandling(() async {
      final streamedResponse = await request.send();
      return http.Response.fromStream(streamedResponse);
    }, timeout: const Duration(seconds: 60));
    return _decode(response);
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
