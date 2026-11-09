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
    return RefreshIndicator(
      onRefresh: () async => setState(() => _people = _loadPeople()),
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const TkPageHeading(eyebrow: 'WORKSPACE · PEOPLE', title: 'Messages'),
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
              if (snapshot.connectionState == ConnectionState.waiting)
                return const Padding(
                  padding: EdgeInsets.all(32),
                  child: Center(child: CircularProgressIndicator()),
                );
              if (snapshot.hasError)
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
              final people = snapshot.data ?? [];
              if (people.isEmpty)
                return Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text(
                    'No active members found.',
                    textAlign: TextAlign.center,
                    style: tkText(context, color: c.muted),
                  ),
                );
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
                                        style: tkText(context, weight: 600),
                                      ),
                                      const SizedBox(height: 3),
                                      Text(
                                        '${raw['role']}',
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
    );
  }
}

class _ConversationView extends StatefulWidget {
  const _ConversationView({required this.person});
  final Map<String, dynamic> person;

  @override
  State<_ConversationView> createState() => _ConversationViewState();
}

class _ConversationViewState extends State<_ConversationView> {
  final _composer = TextEditingController();
  late Future<Map<String, dynamic>> _conversation;
  bool _sending = false;

  int get _personId => widget.person['id'] as int;

  Future<Map<String, dynamic>> _load() =>
      ApiClient.instance.workspace('/messages/$_personId');

  @override
  void initState() {
    super.initState();
    _conversation = _load();
  }

  Future<void> _send() async {
    final body = _composer.text.trim();
    if (body.isEmpty || _sending) return;
    setState(() => _sending = true);
    try {
      await ApiClient.instance.workspace(
        '/messages/$_personId',
        method: 'POST',
        data: {'body': body},
      );
      _composer.clear();
      if (mounted) setState(() => _conversation = _load());
    } on ApiException catch (error) {
      if (mounted)
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  void dispose() {
    _composer.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('${widget.person['name']}'),
            Text(
              '${widget.person['role']}',
              style: tkText(context, size: 12, color: c.muted),
            ),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(
            child: FutureBuilder<Map<String, dynamic>>(
              future: _conversation,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting)
                  return const Center(child: CircularProgressIndicator());
                if (snapshot.hasError)
                  return Center(
                    child: TextButton(
                      onPressed: () => setState(() => _conversation = _load()),
                      child: const Text(
                        'Could not load messages. Tap to retry.',
                      ),
                    ),
                  );
                final messages = asList(snapshot.data?['messages']);
                if (messages.isEmpty)
                  return Center(
                    child: Text(
                      'No messages yet. Say hello.',
                      style: tkText(context, color: c.muted),
                    ),
                  );
                return ListView.builder(
                  reverse: true,
                  padding: const EdgeInsets.all(16),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final message = asMap(
                      messages[messages.length - 1 - index],
                    );
                    final mine =
                        message['sender_id'] == ApiClient.instance.user?['id'];
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
                );
              },
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
                    onPressed: _sending ? null : _send,
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
