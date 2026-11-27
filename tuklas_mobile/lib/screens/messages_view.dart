import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../api.dart';
import '../locale_controller.dart';
import '../theme.dart';
import '../ui.dart';

class MessagesView extends StatefulWidget {
  const MessagesView({super.key});

  @override
  State<MessagesView> createState() => _MessagesViewState();
}

class _MessagesViewState extends State<MessagesView> {
  final _search = TextEditingController();
  late Future<List<dynamic>> _people;

  @override
  void initState() {
    super.initState();
    _people = _loadPeople();
  }

  Future<List<dynamic>> _loadPeople() async {
    final result = await ApiClient.instance.workspace(
      '/messages${_search.text.trim().isEmpty ? '' : '?search=${Uri.encodeQueryComponent(_search.text.trim())}'}',
    );
    return asList(result['people']);
  }

  Future<void> _openConversation(Map<String, dynamic> person) async {
    await Navigator.of(context).push(
      MaterialPageRoute<void>(
        builder: (_) => _ConversationView(person: person),
      ),
    );
    if (mounted) setState(() => _people = _loadPeople());
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(tkL10n(context).messages)),
      body: RefreshIndicator(
        onRefresh: () async => setState(() => _people = _loadPeople()),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            TkPageHeading(
              eyebrow: tkL10n(context).messageWorkspaceEyebrow,
              title: tkL10n(context).messages,
            ),
            Text(
              tkL10n(context).messagePeopleDescription,
              style: tkText(context, color: c.muted),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _search,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => setState(() => _people = _loadPeople()),
              decoration: InputDecoration(
                hintText: tkL10n(context).messagesSearchHint,
                prefixIcon: const Icon(Icons.search),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.arrow_forward),
                  onPressed: () => setState(() => _people = _loadPeople()),
                ),
              ),
            ),
            const SizedBox(height: 12),
            FutureBuilder<List<dynamic>>(
              future: _people,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Padding(
                    padding: EdgeInsets.all(32),
                    child: Center(child: CircularProgressIndicator()),
                  );
                }
                if (snapshot.hasError) {
                  return Center(
                    child: Column(
                      children: [
                        Text(
                          tkL10n(context).couldNotLoadPeople,
                          style: tkText(context, color: c.bad),
                        ),
                        TextButton(
                          onPressed: () =>
                              setState(() => _people = _loadPeople()),
                          child: Text(tkL10n(context).tryAgain),
                        ),
                      ],
                    ),
                  );
                }
                final people = snapshot.data ?? [];
                if (people.isEmpty) {
                  return Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      tkL10n(context).noActiveMembers,
                      textAlign: TextAlign.center,
                      style: tkText(context, color: c.muted),
                    ),
                  );
                }
                return Column(
                  children: [
                    for (final raw in people)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Material(
                          color: c.surface,
                          borderRadius: BorderRadius.circular(16),
                          child: InkWell(
                            borderRadius: BorderRadius.circular(16),
                            onTap: () => _openConversation(asMap(raw)),
                            child: Padding(
                              padding: const EdgeInsets.all(15),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: TkColors.violet.withValues(
                                      alpha: .12,
                                    ),
                                    child: Text(
                                      tkInitials('${raw['name']}'),
                                      style: tkText(
                                        context,
                                        weight: 600,
                                        color: TkColors.violet,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          '${raw['name']}',
                                          maxLines: 2,
                                          overflow: TextOverflow.ellipsis,
                                          style: tkText(context, weight: 600),
                                        ),
                                        const SizedBox(height: 3),
                                        Text(
                                          '${raw['role']}',
                                          maxLines: 1,
                                          overflow: TextOverflow.ellipsis,
                                          style: tkText(
                                            context,
                                            size: 12,
                                            color: c.muted,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                  Icon(Icons.chevron_right, color: c.muted),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _ConversationView extends StatefulWidget {
  const _ConversationView({required this.person});
  final Map<String, dynamic> person;

  @override
  State<_ConversationView> createState() => _ConversationViewState();
}

class _ConversationViewState extends State<_ConversationView>
    with WidgetsBindingObserver {
  static const _maximumAttachmentCount = 5;
  static const _maximumAttachmentBytes = 10 * 1024 * 1024;
  static const _pendingCameraRecipientKey =
      'tuklas_pending_message_camera_recipient';
  final _composer = TextEditingController();
  final _imagePicker = ImagePicker();
  final _messages = <Map<String, dynamic>>[];
  final _attachments = <PlatformFile>[];
  final _attachmentPreviews = <String, Future<Uint8List>>{};
  final _messageIds = <int>{};
  Timer? _refreshTimer;
  WebSocket? _socket;
  StreamSubscription<dynamic>? _socketSubscription;
  Map<String, dynamic> _realtimeConfig = {};
  int _lastFetchedId = 0;
  bool _initialLoading = true;
  bool _loaded = false;
  bool _refreshing = false;
  bool _connectingRealtime = false;
  bool _socketConnected = false;
  bool _sending = false;
  String? _loadError;

  int get _personId => widget.person['id'] as int;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refreshMessages();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) unawaited(_recoverLostCameraPhoto());
    });
    _refreshTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      if (WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed &&
          !_socketConnected) {
        _refreshMessages();
        _connectRealtime();
      }
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refreshMessages();
      _connectRealtime();
    }
  }

  void _addMessage(Map<String, dynamic> message) {
    final id = message['id'];
    if (id is! int || !_messageIds.add(id)) {
      return;
    }
    _messages.add(message);
    _messages.sort(
      (first, second) => (first['id'] as int).compareTo(second['id'] as int),
    );
    if (_messages.length > 100) {
      final removed = _messages.removeAt(0);
      _messageIds.remove(removed['id']);
    }
  }

  Future<void> _refreshMessages() async {
    if (_refreshing) {
      return;
    }
    _refreshing = true;
    try {
      final result = await ApiClient.instance.workspace(
        '/messages/$_personId${_loaded ? '?after=$_lastFetchedId' : ''}',
      );
      if (!mounted) {
        return;
      }
      final fetched = asList(result['messages']).map(asMap).toList();
      final shouldConnectRealtime = !_loaded;
      if (result['realtime'] is Map) {
        _realtimeConfig = asMap(result['realtime']);
      }
      setState(() {
        for (final message in fetched) {
          _addMessage(message);
          final id = message['id'];
          if (id is int && id > _lastFetchedId) {
            _lastFetchedId = id;
          }
        }
        _loaded = true;
        _loadError = null;
      });
      if (shouldConnectRealtime) _connectRealtime();
    } catch (_) {
      if (mounted) {
        setState(
          () => _loadError = _loaded
              ? tkL10n(context).messagesCouldNotSync
              : tkL10n(context).noMessagesRetry,
        );
      }
    } finally {
      _refreshing = false;
      if (mounted && _initialLoading) {
        setState(() => _initialLoading = false);
      }
    }
  }

  Future<void> _connectRealtime() async {
    if (_socketConnected ||
        _connectingRealtime ||
        _realtimeConfig['enabled'] != true) {
      return;
    }

    final key = _realtimeConfig['key'];
    final host = _realtimeConfig['host'];
    final scheme = _realtimeConfig['scheme'];
    if (key is! String ||
        key.isEmpty ||
        host is! String ||
        host.isEmpty ||
        (scheme != 'ws' && scheme != 'wss')) {
      return;
    }

    _connectingRealtime = true;
    WebSocket? socket;
    try {
      final socketUri = Uri(
        scheme: scheme,
        host: host,
        port: _realtimeConfig['port'] as int?,
        path: '/app/$key',
        queryParameters: const {
          'protocol': '7',
          'client': 'flutter',
          'version': '1.0.0',
          'flash': 'false',
        },
      );
      socket = await WebSocket.connect(
        socketUri.toString(),
        headers: {'Accept-Language': localeController.languageCode},
      );
      if (!mounted) {
        await socket.close();
        return;
      }

      _socket = socket;
      _socketSubscription = socket.listen(
        (frame) => unawaited(_handleRealtimeFrame(socket!, frame)),
        onError: (_) => _handleRealtimeDisconnect(socket!),
        onDone: () => _handleRealtimeDisconnect(socket!),
        cancelOnError: true,
      );
    } catch (_) {
      _handleRealtimeDisconnect(socket);
    } finally {
      _connectingRealtime = false;
    }
  }

  Future<void> _handleRealtimeFrame(WebSocket socket, dynamic frame) async {
    if (!identical(_socket, socket) || frame is! String) return;

    try {
      final event = asMap(jsonDecode(frame));
      final eventName = event['event'];
      final rawData = event['data'];
      final data = rawData is String
          ? asMap(jsonDecode(rawData))
          : asMap(rawData);

      if (eventName == 'pusher:ping') {
        socket.add(jsonEncode({'event': 'pusher:pong', 'data': {}}));
        return;
      }

      if (eventName == 'pusher:connection_established') {
        final socketId = data['socket_id'];
        final userId = ApiClient.instance.user?['id'];
        if (socketId is! String || userId is! int) return;

        final authorization = await ApiClient.instance.workspace(
          '/broadcasting/auth',
          method: 'POST',
          data: {
            'socket_id': socketId,
            'channel_name': 'private-users.$userId',
          },
        );
        if (!identical(_socket, socket)) return;
        socket.add(
          jsonEncode({
            'event': 'pusher:subscribe',
            'data': {
              'auth': authorization['auth'],
              'channel': 'private-users.$userId',
            },
          }),
        );
        return;
      }

      if (eventName == 'pusher:subscription_succeeded') {
        _socketConnected = true;
        if (mounted) setState(() => _loadError = null);
        await _refreshMessages();
        return;
      }

      if (eventName == 'message.sent') {
        if (mounted) {
          setState(() {
            _addMessage(data);
            final id = data['id'];
            if (id is int && id > _lastFetchedId) _lastFetchedId = id;
          });
        }
      }
    } catch (_) {
      _handleRealtimeDisconnect(socket);
    }
  }

  void _handleRealtimeDisconnect(WebSocket? socket) {
    if (socket != null && !identical(_socket, socket)) return;
    _socketConnected = false;
    _socket = null;
    _socketSubscription = null;
  }

  Future<void> _send() async {
    final body = _composer.text.trim();
    if ((body.isEmpty && _attachments.isEmpty) || _sending || !_loaded) {
      return;
    }
    final sendingAttachments = List<PlatformFile>.of(_attachments);
    setState(() => _sending = true);
    try {
      final response = await ApiClient.instance.sendMessage(
        recipientId: _personId,
        body: body,
        attachments: sendingAttachments,
      );
      if (!mounted) {
        return;
      }
      if (_composer.text.trim() == body) {
        _composer.clear();
      }
      setState(() {
        _attachments.removeWhere(sendingAttachments.contains);
        _addMessage(asMap(response['message']));
      });
      if (sendingAttachments.isNotEmpty) {
        ScaffoldMessenger.of(
          context,
        ).showSnackBar(SnackBar(content: Text(tkL10n(context).attachmentSent)));
      }
    } on ApiException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(tkL10n(context).couldNotSendMessage)),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  Future<void> _pickDocuments() async {
    try {
      final result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: const [
          'pdf',
          'jpg',
          'jpeg',
          'png',
          'webp',
          'doc',
          'docx',
        ],
        allowMultiple: true,
        withData: true,
      );
      if (result != null && mounted) {
        _addAttachments(result.files);
      }
    } on Exception catch (_) {
      if (mounted) {
        _showAttachmentError(tkL10n(context).couldNotOpenFiles);
      }
    }
  }

  Future<void> _pickPhoto(ImageSource source) async {
    final shouldRecoverAfterRestart =
        Platform.isAndroid && source == ImageSource.camera;
    try {
      if (shouldRecoverAfterRestart) {
        try {
          final preferences = await SharedPreferences.getInstance();
          await preferences.setInt(_pendingCameraRecipientKey, _personId);
        } on Exception catch (_) {
          // Camera capture should still work if recovery storage is unavailable.
        }
      }
      final photo = await _imagePicker.pickImage(
        source: source,
        imageQuality: 82,
        maxWidth: 1920,
        maxHeight: 1920,
      );
      if (photo == null || !mounted) {
        if (shouldRecoverAfterRestart) {
          await _clearPendingCameraRecipient();
        }
        return;
      }
      final bytes = await photo.readAsBytes();
      if (shouldRecoverAfterRestart) {
        await _clearPendingCameraRecipient();
      }
      final attached = _addAttachments([
        PlatformFile(
          name: _photoFileName(photo),
          size: bytes.length,
          bytes: bytes,
        ),
      ]);
      if (attached && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(tkL10n(context).photoAttachedSend)),
        );
      }
    } on Exception catch (_) {
      if (shouldRecoverAfterRestart) {
        await _clearPendingCameraRecipient();
      }
      if (mounted) {
        _showAttachmentError(tkL10n(context).couldNotAccessImage);
      }
    }
  }

  Future<void> _recoverLostCameraPhoto() async {
    if (!Platform.isAndroid) return;

    try {
      final preferences = await SharedPreferences.getInstance();
      if (preferences.getInt(_pendingCameraRecipientKey) != _personId) {
        return;
      }

      final result = await _imagePicker.retrieveLostData();
      final photos =
          result.files ?? (result.file == null ? [] : [result.file!]);
      final recovered = <PlatformFile>[];
      for (final photo in photos) {
        final bytes = await photo.readAsBytes();
        recovered.add(
          PlatformFile(
            name: _photoFileName(photo),
            size: bytes.length,
            bytes: bytes,
          ),
        );
      }

      await preferences.remove(_pendingCameraRecipientKey);
      if (!mounted) return;
      if (recovered.isNotEmpty) {
        if (_addAttachments(recovered)) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text(tkL10n(context).photoRecoveredSend)),
          );
        }
      } else if (result.exception != null) {
        _showAttachmentError(tkL10n(context).cameraNoPhoto);
      }
    } on Exception catch (_) {
      await _clearPendingCameraRecipient();
      if (mounted) {
        _showAttachmentError(tkL10n(context).couldNotRecoverPhoto);
      }
    }
  }

  Future<void> _clearPendingCameraRecipient() async {
    try {
      final preferences = await SharedPreferences.getInstance();
      await preferences.remove(_pendingCameraRecipientKey);
    } on Exception catch (_) {
      // Camera capture can still proceed when local preference storage fails.
    }
  }

  String _photoFileName(XFile photo) {
    if (_isImageName(photo.name)) return photo.name;

    final extension = photo.path.split('.').last.toLowerCase();
    final safeExtension =
        const {'jpg', 'jpeg', 'png', 'webp'}.contains(extension)
        ? extension
        : 'jpg';
    return 'tuklas-photo-${DateTime.now().millisecondsSinceEpoch}.$safeExtension';
  }

  bool _isImageName(String name) {
    final extension = name.split('.').last.toLowerCase();
    return const {'jpg', 'jpeg', 'png', 'webp'}.contains(extension);
  }

  bool _isImageAttachment(Map<String, dynamic> attachment) {
    final mime = '${attachment['mime'] ?? ''}'.toLowerCase();
    final name = '${attachment['name'] ?? ''}';
    return mime.startsWith('image/') || _isImageName(name);
  }

  Future<Uint8List> _loadAttachmentPreview(
    Map<String, dynamic> message,
    Map<String, dynamic> attachment,
  ) {
    final key = '${message['id']}:${attachment['index']}';
    return _attachmentPreviews.putIfAbsent(key, () async {
      try {
        return await ApiClient.instance.downloadMessageAttachment(
          personId: _personId,
          messageId: message['id'] as int,
          attachmentIndex: attachment['index'] as int,
        );
      } catch (_) {
        _attachmentPreviews.remove(key);
        rethrow;
      }
    });
  }

  Widget _imageAttachmentPreview(
    BuildContext context,
    Map<String, dynamic> message,
    Map<String, dynamic> attachment,
  ) {
    final width = (MediaQuery.sizeOf(context).width * .68)
        .clamp(150.0, 220.0)
        .toDouble();
    return FutureBuilder<Uint8List>(
      future: _loadAttachmentPreview(message, attachment),
      builder: (context, snapshot) {
        final preview = snapshot.data;
        if (preview == null) {
          return Container(
            width: width,
            height: 120,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: TkColors.of(context).surface,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              snapshot.hasError
                  ? tkL10n(context).previewUnavailableTapSave
                  : tkL10n(context).loadingPhoto,
              textAlign: TextAlign.center,
              style: tkText(
                context,
                size: 12,
                color: TkColors.of(context).muted,
              ),
            ),
          );
        }

        return ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: Image.memory(
            preview,
            width: width,
            height: 170,
            fit: BoxFit.cover,
            gaplessPlayback: true,
            errorBuilder: (_, _, _) => SizedBox(
              width: width,
              height: 120,
              child: Center(
                child: Text(
                  tkL10n(context).previewUnavailableTapSave,
                  textAlign: TextAlign.center,
                  style: tkText(
                    context,
                    size: 12,
                    color: TkColors.of(context).muted,
                  ),
                ),
              ),
            ),
          ),
        );
      },
    );
  }

  bool _addAttachments(List<PlatformFile> selected) {
    final remaining = _maximumAttachmentCount - _attachments.length;
    if (selected.length > remaining) {
      _showAttachmentError(
        tkL10n(context).attachLimit(_maximumAttachmentCount),
      );
      return false;
    }
    for (final file in selected) {
      if (file.size > _maximumAttachmentBytes) {
        _showAttachmentError(tkL10n(context).attachmentTooLarge(file.name));
        return false;
      }
    }
    setState(() => _attachments.addAll(selected));
    return true;
  }

  void _showAttachmentError(String message) {
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(message)));
  }

  Future<void> _showAttachmentOptions() async {
    await showModalBottomSheet<void>(
      context: context,
      builder: (sheetContext) => SafeArea(
        child: Wrap(
          children: [
            ListTile(
              leading: const Icon(Icons.attach_file),
              title: Text(tkL10n(sheetContext).chooseFilesOrPhotos),
              onTap: () {
                Navigator.pop(sheetContext);
                _pickDocuments();
              },
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(tkL10n(sheetContext).choosePicture),
              onTap: () {
                Navigator.pop(sheetContext);
                _pickPhoto(ImageSource.gallery);
              },
            ),
            ListTile(
              leading: const Icon(Icons.camera_alt_outlined),
              title: Text(tkL10n(sheetContext).takePicture),
              onTap: () {
                Navigator.pop(sheetContext);
                _pickPhoto(ImageSource.camera);
              },
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _downloadAttachment(
    Map<String, dynamic> message,
    Map<String, dynamic> attachment,
  ) async {
    try {
      final bytes = await ApiClient.instance.downloadMessageAttachment(
        personId: _personId,
        messageId: message['id'] as int,
        attachmentIndex: attachment['index'] as int,
      );
      final saved = await FilePicker.saveFile(
        fileName: '${attachment['name'] ?? 'tuklas-attachment'}',
        bytes: bytes,
      );
      if (mounted && saved != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(tkL10n(context).attachmentSaved)),
        );
      }
    } on ApiException catch (error) {
      if (mounted) {
        _showAttachmentError(error.message);
      }
    } on Exception catch (_) {
      if (mounted) {
        _showAttachmentError(tkL10n(context).couldNotSaveAttachment);
      }
    }
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
    _socketSubscription?.cancel();
    _socket?.close();
    WidgetsBinding.instance.removeObserver(this);
    _composer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 72,
        title: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${widget.person['name']}',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
            Text(
              '${widget.person['role']}',
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: tkText(context, size: 12, color: c.muted),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: _initialLoading
                ? const Center(child: CircularProgressIndicator())
                : !_loaded
                ? Center(
                    child: TextButton(
                      onPressed: _refreshMessages,
                      child: Text(
                        _loadError ?? tkL10n(context).noMessagesRetry,
                      ),
                    ),
                  )
                : _messages.isEmpty
                ? Center(
                    child: Text(
                      tkL10n(context).noMessagesYet,
                      style: tkText(context, color: c.muted),
                    ),
                  )
                : ListView.builder(
                    reverse: true,
                    padding: const EdgeInsets.all(16),
                    itemCount: _messages.length,
                    itemBuilder: (context, index) {
                      final message = _messages[_messages.length - 1 - index];
                      final mine =
                          message['sender_id'] ==
                          ApiClient.instance.user?['id'];
                      return Align(
                        alignment: mine
                            ? Alignment.centerRight
                            : Alignment.centerLeft,
                        child: Container(
                          constraints: BoxConstraints(
                            maxWidth: MediaQuery.sizeOf(context).width * .78,
                          ),
                          margin: const EdgeInsets.only(bottom: 10),
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 11,
                          ),
                          decoration: BoxDecoration(
                            color: mine ? TkColors.violet : c.surface2,
                            borderRadius: BorderRadius.circular(16),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if ('${message['body'] ?? ''}'.isNotEmpty)
                                Text(
                                  '${message['body']}',
                                  style: tkText(
                                    context,
                                    color: mine ? Colors.white : c.ink,
                                  ),
                                ),
                              for (final rawAttachment in asList(
                                message['attachments'],
                              ))
                                Builder(
                                  builder: (context) {
                                    final attachment = asMap(rawAttachment);
                                    return InkWell(
                                      onTap: () => _downloadAttachment(
                                        message,
                                        attachment,
                                      ),
                                      child: Padding(
                                        padding: const EdgeInsets.only(top: 8),
                                        child: Column(
                                          crossAxisAlignment:
                                              CrossAxisAlignment.start,
                                          children: [
                                            if (_isImageAttachment(
                                              attachment,
                                            )) ...[
                                              _imageAttachmentPreview(
                                                context,
                                                message,
                                                attachment,
                                              ),
                                              const SizedBox(height: 5),
                                            ],
                                            Row(
                                              mainAxisSize: MainAxisSize.min,
                                              children: [
                                                Icon(
                                                  _isImageAttachment(attachment)
                                                      ? Icons.image_outlined
                                                      : Icons.attach_file,
                                                  size: 18,
                                                ),
                                                const SizedBox(width: 5),
                                                Flexible(
                                                  child: Text(
                                                    tkL10n(
                                                      context,
                                                    ).attachmentNameTapSave(
                                                      '${attachment['name'] ?? 'Attachment'}',
                                                    ),
                                                    maxLines: 2,
                                                    overflow:
                                                        TextOverflow.ellipsis,
                                                    style: tkText(
                                                      context,
                                                      color: mine
                                                          ? Colors.white
                                                          : c.ink,
                                                      size: 13,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                    );
                                  },
                                ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
          if (_loaded && _loadError != null)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
              child: Text(
                _loadError!,
                style: tkText(context, size: 12, color: c.bad),
              ),
            ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 10),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_attachments.isNotEmpty)
                    SizedBox(
                      height: 44,
                      child: ListView.separated(
                        scrollDirection: Axis.horizontal,
                        itemCount: _attachments.length,
                        separatorBuilder: (_, _) => const SizedBox(width: 8),
                        itemBuilder: (context, index) {
                          final file = _attachments[index];
                          return InputChip(
                            avatar:
                                file.bytes != null && _isImageName(file.name)
                                ? ClipOval(
                                    child: Image.memory(
                                      file.bytes!,
                                      width: 24,
                                      height: 24,
                                      fit: BoxFit.cover,
                                    ),
                                  )
                                : const Icon(Icons.attach_file, size: 16),
                            label: ConstrainedBox(
                              constraints: const BoxConstraints(maxWidth: 180),
                              child: Text(
                                file.name,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            onDeleted: _sending
                                ? null
                                : () => setState(
                                    () => _attachments.removeAt(index),
                                  ),
                          );
                        },
                      ),
                    ),
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      IconButton(
                        onPressed: _sending || !_loaded
                            ? null
                            : _showAttachmentOptions,
                        icon: const Icon(Icons.add_circle_outline),
                        tooltip: tkL10n(context).attachFileOrPicture,
                      ),
                      Expanded(
                        child: TextField(
                          controller: _composer,
                          minLines: 1,
                          maxLines: 4,
                          maxLength: 2000,
                          decoration: InputDecoration(
                            hintText: tkL10n(context).writeMessage,
                            counterText: '',
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      IconButton.filled(
                        onPressed: _sending || !_loaded ? null : _send,
                        icon: _sending
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                ),
                              )
                            : const Icon(Icons.send),
                        tooltip: tkL10n(context).sendMessage,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
