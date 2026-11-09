import 'dart:async';

import 'package:flutter/material.dart';

import '../api.dart';
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
      appBar: AppBar(title: const Text('Messages')),
      body: RefreshIndicator(
        onRefresh: () async => setState(() => _people = _loadPeople()),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            const TkPageHeading(
              eyebrow: 'WORKSPACE · PEOPLE',
              title: 'Messages',
            ),
            Text(
              'Message administrators, trainers, and other active Tuklas members.',
              style: tkText(context, color: c.muted),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: _search,
              textInputAction: TextInputAction.search,
              onSubmitted: (_) => setState(() => _people = _loadPeople()),
              decoration: InputDecoration(
                hintText: 'Find a person',
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
                          'Could not load people.',
                          style: tkText(context, color: c.bad),
                        ),
                        TextButton(
                          onPressed: () =>
                              setState(() => _people = _loadPeople()),
                          child: const Text('Try again'),
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
                      'No active members found.',
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
  final _composer = TextEditingController();
  final _messages = <Map<String, dynamic>>[];
  final _messageIds = <int>{};
  Timer? _refreshTimer;
  int _lastFetchedId = 0;
  bool _initialLoading = true;
  bool _loaded = false;
  bool _refreshing = false;
  bool _sending = false;
  String? _loadError;

  int get _personId => widget.person['id'] as int;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _refreshMessages();
    _refreshTimer = Timer.periodic(const Duration(seconds: 3), (_) {
      if (WidgetsBinding.instance.lifecycleState == AppLifecycleState.resumed) {
        _refreshMessages();
      }
    });
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      _refreshMessages();
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
    } catch (_) {
      if (mounted) {
        setState(
          () => _loadError = _loaded
              ? 'Messages could not sync. Retrying…'
              : 'Could not load messages. Tap to retry.',
        );
      }
    } finally {
      _refreshing = false;
      if (mounted && _initialLoading) {
        setState(() => _initialLoading = false);
      }
    }
  }

  Future<void> _send() async {
    final body = _composer.text.trim();
    if (body.isEmpty || _sending || !_loaded) {
      return;
    }
    setState(() => _sending = true);
    try {
      final response = await ApiClient.instance.workspace(
        '/messages/$_personId',
        method: 'POST',
        data: {'body': body},
      );
      if (!mounted) {
        return;
      }
      if (_composer.text.trim() == body) {
        _composer.clear();
      }
      setState(() => _addMessage(asMap(response['message'])));
    } on ApiException catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text(
              'Could not send message. Check your connection and try again.',
            ),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _sending = false);
      }
    }
  }

  @override
  void dispose() {
    _refreshTimer?.cancel();
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
                        _loadError ?? 'Could not load messages. Tap to retry.',
                      ),
                    ),
                  )
                : _messages.isEmpty
                ? Center(
                    child: Text(
                      'No messages yet. Say hello.',
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
                          child: Text(
                            '${message['body']}',
                            style: tkText(
                              context,
                              color: mine ? Colors.white : c.ink,
                            ),
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
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Expanded(
                    child: TextField(
                      controller: _composer,
                      minLines: 1,
                      maxLines: 4,
                      maxLength: 2000,
                      decoration: const InputDecoration(
                        hintText: 'Write a message',
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
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.send),
                    tooltip: 'Send message',
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
