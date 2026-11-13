import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/material.dart';

import 'api.dart';

/// Keeps the active mobile workspace current when shared Tuklas data changes.
class WorkspaceRealtime {
  WorkspaceRealtime._();

  static final WorkspaceRealtime instance = WorkspaceRealtime._();

  final ValueNotifier<int> revision = ValueNotifier(0);
  WebSocket? _socket;
  StreamSubscription<dynamic>? _subscription;
  Timer? _retryTimer;
  bool _connecting = false;
  bool _enabled = true;
  String? latestResource;

  bool get isConnected => _socket != null;

  Future<void> connect() async {
    _enabled = true;
    if (_connecting || _socket != null) return;
    _connecting = true;

    try {
      final result = await ApiClient.instance.workspace('/realtime');
      if (!_enabled) return;
      final config = result['realtime'];
      if (config is! Map || config['enabled'] != true) {
        _scheduleReconnect();
        return;
      }

      final key = config['key'];
      final host = config['host'];
      final scheme = config['scheme'];
      if (key is! String ||
          key.isEmpty ||
          host is! String ||
          host.isEmpty ||
          (scheme != 'ws' && scheme != 'wss')) {
        _scheduleReconnect();
        return;
      }

      final socketUri = Uri(
        scheme: scheme,
        host: host,
        port: config['port'] as int?,
        path: '/app/$key',
        queryParameters: const {
          'protocol': '7',
          'client': 'flutter',
          'version': '1.0.0',
          'flash': 'false',
        },
      );
      final socket = await WebSocket.connect(socketUri.toString());
      if (!_enabled) {
        await socket.close();
        return;
      }
      _socket = socket;
      _subscription = socket.listen(
        (frame) => unawaited(_handleFrame(socket, frame)),
        onError: (_) => _disconnect(socket),
        onDone: () => _disconnect(socket),
        cancelOnError: true,
      );
    } catch (_) {
      _disconnect(_socket);
    } finally {
      _connecting = false;
    }
  }

  Future<void> _handleFrame(WebSocket socket, dynamic frame) async {
    if (!identical(_socket, socket) || frame is! String) return;

    try {
      final event = _asMap(jsonDecode(frame));
      final name = event['event'];
      final rawData = event['data'];
      final data = rawData is String
          ? _asMap(jsonDecode(rawData))
          : _asMap(rawData);

      if (name == 'pusher:ping') {
        socket.add(jsonEncode({'event': 'pusher:pong', 'data': {}}));
        return;
      }

      if (name == 'pusher:connection_established') {
        final socketId = data['socket_id'];
        if (socketId is! String) return;
        final channel = 'private-workspace';
        final authorization = await ApiClient.instance.workspace(
          '/broadcasting/auth',
          method: 'POST',
          data: {'socket_id': socketId, 'channel_name': channel},
        );
        if (!identical(_socket, socket)) return;
        socket.add(
          jsonEncode({
            'event': 'pusher:subscribe',
            'data': {'auth': authorization['auth'], 'channel': channel},
          }),
        );
        final userId = ApiClient.instance.user?['id'];
        if (userId is int) {
          final userChannel = 'private-users.$userId';
          final userAuthorization = await ApiClient.instance.workspace(
            '/broadcasting/auth',
            method: 'POST',
            data: {'socket_id': socketId, 'channel_name': userChannel},
          );
          if (!identical(_socket, socket)) return;
          socket.add(
            jsonEncode({
              'event': 'pusher:subscribe',
              'data': {'auth': userAuthorization['auth'], 'channel': userChannel},
            }),
          );
        }
        return;
      }

      if (name == 'pusher:subscription_succeeded') {
        _retryTimer?.cancel();
        latestResource = null;
        revision.value++;
        return;
      }

      if (name == 'pusher:subscription_error') {
        await socket.close();
        _disconnect(socket);
        return;
      }

      if (name == 'workspace.changed') {
        final actorId = data['actor_id'];
        final currentUserId = ApiClient.instance.user?['id'];
        final lastWriteAt = ApiClient.instance.lastWriteAt;
        if (actorId == currentUserId &&
            lastWriteAt != null &&
            DateTime.now().difference(lastWriteAt).inSeconds < 4) {
          return;
        }
        latestResource = data['resource'] as String?;
        revision.value++;
      }

      if (name == 'notifications.changed') {
        latestResource = 'notifications';
        revision.value++;
      }
    } catch (_) {
      _disconnect(socket);
    }
  }

  Map<String, dynamic> _asMap(dynamic value) =>
      value is Map<String, dynamic> ? value : <String, dynamic>{};

  void _disconnect(WebSocket? socket) {
    if (socket != null && !identical(_socket, socket)) return;
    _socket = null;
    _subscription = null;
    _scheduleReconnect();
  }

  void _scheduleReconnect() {
    if (!_enabled || _retryTimer?.isActive == true) return;
    _retryTimer = Timer(const Duration(seconds: 4), () {
      unawaited(connect());
    });
  }

  void reconnect() {
    revision.value++;
    if (_socket == null) unawaited(connect());
  }

  Future<void> disconnect() async {
    _enabled = false;
    _retryTimer?.cancel();
    await _subscription?.cancel();
    await _socket?.close();
    _socket = null;
  }
}

/// Rebuilds the active screen for new server data without interrupting typing.
class WorkspaceRefreshScope extends StatefulWidget {
  const WorkspaceRefreshScope({super.key, required this.child});

  final Widget child;

  @override
  State<WorkspaceRefreshScope> createState() => _WorkspaceRefreshScopeState();
}

class _WorkspaceRefreshScopeState extends State<WorkspaceRefreshScope> {
  late int _revision = WorkspaceRealtime.instance.revision.value;
  bool _pending = false;

  @override
  void initState() {
    super.initState();
    WorkspaceRealtime.instance.revision.addListener(_onWorkspaceChanged);
    FocusManager.instance.addListener(_onFocusChanged);
  }

  @override
  void dispose() {
    WorkspaceRealtime.instance.revision.removeListener(_onWorkspaceChanged);
    FocusManager.instance.removeListener(_onFocusChanged);
    super.dispose();
  }

  bool get _editorHasFocus =>
      FocusManager.instance.primaryFocus?.context?.widget is EditableText;

  void _onWorkspaceChanged() {
    if (WorkspaceRealtime.instance.latestResource == 'messages') return;
    if (_editorHasFocus) {
      _pending = true;
      return;
    }
    _applyWorkspaceChange();
  }

  void _onFocusChanged() {
    if (_pending && !_editorHasFocus) {
      _pending = false;
      _applyWorkspaceChange();
    }
  }

  void _applyWorkspaceChange() {
    if (!mounted) return;
    setState(() => _revision = WorkspaceRealtime.instance.revision.value);
  }

  @override
  Widget build(BuildContext context) =>
      KeyedSubtree(key: ValueKey('workspace-$_revision'), child: widget.child);
}
