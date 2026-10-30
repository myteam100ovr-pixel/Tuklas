import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class ChatView extends StatelessWidget {
  const ChatView({super.key});
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('TuklasBot')),
    body: const SingleChildScrollView(
      padding: EdgeInsets.all(16),
      child: CareerChatCard(),
    ),
  );
}

class CareerChatCard extends StatefulWidget {
  const CareerChatCard({super.key});

  @override
  State<CareerChatCard> createState() => CareerChatCardState();
}

class CareerChatCardState extends State<CareerChatCard> {
  final _messageController = TextEditingController();
  final _messages = <Map<String, dynamic>>[];
  bool _sending = false;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    try {
      final data = await ApiClient.instance.workspace('/career-chat');
      if (!mounted) return;
      setState(() {
        _messages.clear();
        _messages.addAll(asList(data['messages']).map((m) => asMap(m)));
        _error = null;
      });
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = 'Could not load conversation history. Try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String? _error;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _send([String? prompt]) async {
    final text = (prompt ?? _messageController.text).trim();
    if (text.isEmpty || _sending || _loading) return;

    _messageController.clear();
    final history = _messages.skip(
      (_messages.length - 9).clamp(0, _messages.length),
    );
    final requestMessages = [
      ...history.map(
        (message) => {
          'role': message['role'] as String,
          'text': message['text'] as String,
        },
      ),
      {'role': 'user', 'text': text},
    ];
    setState(() {
      _messages.add({'role': 'user', 'text': text, 'sources': const []});
      if (_messages.length > 20) {
        _messages.removeRange(0, _messages.length - 20);
      }
      _sending = true;
      _error = null;
    });

    try {
      final response = await ApiClient.instance.careerChat(requestMessages);
      if (!mounted) return;
      final sources = response['sources'] is List
          ? (response['sources'] as List)
                .whereType<Map>()
                .map((source) => Map<String, dynamic>.from(source))
                .toList()
          : <Map<String, dynamic>>[];
      setState(() {
        _messages.add({
          'role': 'model',
          'text': response['reply'] as String,
          'sources': sources,
        });
        if (_messages.length > 20) {
          _messages.removeRange(0, _messages.length - 20);
        }
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _messageController.text = text;
        _messages.removeLast();
        _error = error is ApiException ? error.message : 'Could not reach the career assistant. Check your connection and try again.';
      });
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  Future<void> _openSource(String rawUrl) async {
    final uri = Uri.tryParse(rawUrl);

    if (uri == null || !['http', 'https'].contains(uri.scheme)) {
      return;
    }

    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);

    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open this source.')),
      );
    }
  }

  List<Widget> _sourceLinks(BuildContext context, dynamic rawSources) {
    if (rawSources is! List) {
      return const [];
    }

    final colors = TkColors.of(context);
    final links = rawSources
        .whereType<Map>()
        .map((source) {
          final url = source['url'];
          final title = source['title'];
          final uri = url is String ? Uri.tryParse(url) : null;

          if (uri == null || !['http', 'https'].contains(uri.scheme)) {
            return null;
          }

          return ActionChip(
            avatar: const Icon(
              Icons.open_in_new,
              size: 14,
              color: TkColors.violet,
            ),
            label: Text(title is String && title.isNotEmpty ? title : uri.host),
            visualDensity: VisualDensity.compact,
            onPressed: () => _openSource(uri.toString()),
          );
        })
        .whereType<Widget>()
        .toList();

    if (links.isEmpty) {
      return const [];
    }

    return [
      const SizedBox(height: 6),
      Text('Sources', style: tkText(context, size: 11.5, color: colors.muted)),
      const SizedBox(height: 3),
      Wrap(spacing: 4, runSpacing: 2, children: links),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);
    final suggestions = [
      'Explore careers',
      'AI skills + TESDA',
      'Plan a next step',
      'Build my skills',
    ];
    final prompts = [
      'Help me explore careers that match my interests.',
      'I want to work in AI. What skills should I develop, and which TESDA NC or online TESDA course should I consider first?',
      'Help me make a practical plan for my next step.',
      'What skills should I build for the kind of work I want?',
    ];

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(
              'How can I help you?',
              style: tkText(context, size: 17, weight: 500, spacing: -0.2),
            ),
          ),
          Text(
            'Ask Tuklas about careers, AI skills, TESDA training, or a practical next step.',
            style: tkText(context, size: 13, color: colors.muted, height: 1.4),
          ),
          const SizedBox(height: 12),
          if (_loading) const LinearProgressIndicator(),
          if (_error != null)
            TextButton(
              onPressed: _loadHistory,
              child: const Text('Reload conversation'),
            ),
          if (_messages.isNotEmpty)
            Container(
              constraints: const BoxConstraints(maxHeight: 260),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: colors.surface2,
                borderRadius: BorderRadius.circular(14),
              ),
              child: ListView(
                shrinkWrap: true,
                children: [
                  for (final message in _messages)
                    Align(
                      alignment: message['role'] == 'user'
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: Container(
                        constraints: const BoxConstraints(maxWidth: 280),
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: message['role'] == 'user'
                              ? TkColors.violet
                              : colors.surface,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              message['text'] as String,
                              style: tkText(
                                context,
                                size: 13,
                                color: message['role'] == 'user'
                                    ? Colors.white
                                    : colors.ink,
                                height: 1.4,
                              ),
                            ),
                            if (message['role'] != 'user')
                              ..._sourceLinks(context, message['sources']),
                          ],
                        ),
                      ),
                    ),
                  if (_sending)
                    const Align(
                      alignment: Alignment.centerLeft,
                      child: Padding(
                        padding: EdgeInsets.all(8),
                        child: SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          if (_error != null) ...[
            const SizedBox(height: 8),
            Text(
              _error!,
              style: tkText(context, size: 12.5, color: colors.bad),
            ),
          ],
          const SizedBox(height: 12),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (var i = 0; i < suggestions.length; i++)
                ActionChip(
                  label: Text(suggestions[i]),
                  onPressed: _sending || _loading
                      ? null
                      : () => _send(prompts[i]),
                ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _messageController,
                  maxLength: 2000,
                  maxLines: 2,
                  minLines: 1,
                  enabled: !_sending && !_loading,
                  decoration: const InputDecoration(
                    hintText: 'Ask something...',
                    counterText: '',
                  ),
                  onSubmitted: (_) => _send(),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                onPressed: _sending || _loading ? null : _send,
                tooltip: 'Send message',
                icon: const Icon(Icons.arrow_upward),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Messages are sent to Google Gemini. For career questions, limited skills and job matches from your latest resume or certificate scan may also be shared to personalize suggestions.',
            style: tkText(context, size: 11.5, color: colors.muted),
          ),
        ],
      ),
    );
  }
}
