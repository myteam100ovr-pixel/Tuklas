import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../locale_controller.dart';
import '../theme.dart';
import '../ui.dart';

class ChatView extends StatelessWidget {
  const ChatView({super.key, this.onOpenLink});

  final ValueChanged<String>? onOpenLink;
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('TuklasBot')),
    body: SingleChildScrollView(
      padding: EdgeInsets.all(16),
      child: CareerChatCard(onOpenLink: onOpenLink),
    ),
  );
}

class CareerChatCard extends StatefulWidget {
  const CareerChatCard({super.key, this.onOpenLink});

  final ValueChanged<String>? onOpenLink;

  @override
  State<CareerChatCard> createState() => CareerChatCardState();
}

class CareerChatCardState extends State<CareerChatCard> {
  final _messageController = TextEditingController();
  final _chatScrollController = ScrollController();
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
      _scrollToLatest();
    } catch (e) {
      if (mounted) {
        setState(() => _error = tkL10n(context).couldNotLoadConversation);
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String? _error;

  @override
  void dispose() {
    _messageController.dispose();
    _chatScrollController.dispose();
    super.dispose();
  }

  void _scrollToLatest() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted && _chatScrollController.hasClients) {
        _chatScrollController.animateTo(
          _chatScrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 180),
          curve: Curves.easeOut,
        );
      }
    });
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
    _scrollToLatest();

    try {
      final response = await ApiClient.instance.careerChat(requestMessages);
      if (!mounted) return;
      final sources = response['sources'] is List
          ? (response['sources'] as List)
                .whereType<Map>()
                .map((source) => Map<String, dynamic>.from(source))
                .toList()
          : <Map<String, dynamic>>[];
      final internalLinks = response['recommended_links'] is List
          ? (response['recommended_links'] as List)
                .whereType<Map>()
                .map((link) => Map<String, dynamic>.from(link))
                .map(
                  (link) => <String, dynamic>{
                    'type': 'internal',
                    'title': link['label'] ?? tkL10n(context).openResource,
                    'url': link['web_url'],
                    'mobile_route': link['mobile_route'],
                  },
                )
                .toList()
          : <Map<String, dynamic>>[];
      setState(() {
        _messages.add({
          'role': 'model',
          'text': response['reply'] as String,
          'sources': [...sources, ...internalLinks],
        });
        if (_messages.length > 20) {
          _messages.removeRange(0, _messages.length - 20);
        }
      });
      _scrollToLatest();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        if (_messageController.text.trim().isEmpty) {
          _messageController.text = text;
        }
        _messages.removeLast();
        _error = error is ApiException
            ? error.message
            : tkL10n(context).couldNotReachCareerAssistant;
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
        SnackBar(content: Text(tkL10n(context).couldNotOpenSource)),
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
        .where((source) => source['type'] != 'internal')
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
      Text(
        tkL10n(context).sources,
        style: tkText(context, size: 11.5, color: colors.muted),
      ),
      const SizedBox(height: 3),
      Wrap(spacing: 4, runSpacing: 2, children: links),
    ];
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);
    final l10n = tkL10n(context);
    final isTagalog = localeController.languageCode == 'tl';
    final suggestions = [
      l10n.exploreCareers,
      l10n.aiSkillsTesda,
      l10n.planNextStep,
      l10n.buildMySkills,
    ];
    final prompts = isTagalog
        ? const [
            'Tulungan akong tuklasin ang mga karerang tugma sa mga interes ko.',
            'Gusto kong magtrabaho sa larangan ng AI. Anong mga kasanayan ang dapat kong paunlarin, at anong TESDA NC o online TESDA course ang dapat kong unahin?',
            'Tulungan akong gumawa ng praktikal na plano para sa susunod kong hakbang.',
            'Anong mga kasanayan ang dapat kong paunlarin para sa trabahong gusto ko?',
          ]
        : [
            l10n.promptExploreCareers,
            l10n.promptAiSkillsTesda,
            l10n.promptPlanNextStep,
            l10n.promptBuildSkills,
          ];

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Text(
              l10n.chatHowCanIHelp,
              style: tkText(context, size: 17, weight: 500, spacing: -0.2),
            ),
          ),
          Text(
            l10n.chatIntro,
            style: tkText(context, size: 13, color: colors.muted, height: 1.4),
          ),
          const SizedBox(height: 12),
          if (_loading) const LinearProgressIndicator(),
          if (_error != null && _messages.isEmpty)
            TextButton(
              onPressed: _loadHistory,
              child: Text(l10n.reloadConversation),
            ),
          if (_messages.isNotEmpty)
            Container(
              constraints: BoxConstraints(
                maxHeight: (MediaQuery.sizeOf(context).height * .48).clamp(
                  260.0,
                  540.0,
                ),
              ),
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: colors.surface2,
                borderRadius: BorderRadius.circular(14),
              ),
              child: ListView(
                controller: _chatScrollController,
                shrinkWrap: true,
                children: [
                  for (final message in _messages)
                    Align(
                      alignment: message['role'] == 'user'
                          ? Alignment.centerRight
                          : Alignment.centerLeft,
                      child: Container(
                        constraints: BoxConstraints(
                          maxWidth: (MediaQuery.sizeOf(context).width * .72)
                              .clamp(200.0, 480.0),
                        ),
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
                            _ChatRichText(
                              text: message['text'] as String,
                              sources: message['sources'] is List
                                  ? (message['sources'] as List)
                                        .whereType<Map>()
                                        .toList()
                                  : const [],
                              onOpenLink: widget.onOpenLink,
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
                  enabled: !_loading,
                  decoration: InputDecoration(
                    hintText: l10n.askSomething,
                    counterText: '',
                  ),
                  onSubmitted: (_) => _send(),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                onPressed: _sending || _loading ? null : _send,
                tooltip: l10n.sendMessage,
                icon: const Icon(Icons.arrow_upward),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            l10n.chatPrivacyNote,
            style: tkText(context, size: 11.5, color: colors.muted),
          ),
        ],
      ),
    );
  }
}

class _ChatRichText extends StatefulWidget {
  const _ChatRichText({
    required this.text,
    required this.sources,
    required this.style,
    this.onOpenLink,
  });

  final String text;
  final List<Map> sources;
  final TextStyle style;
  final ValueChanged<String>? onOpenLink;

  @override
  State<_ChatRichText> createState() => _ChatRichTextState();
}

class _ChatRichTextState extends State<_ChatRichText> {
  final List<TapGestureRecognizer> _recognizers = [];

  @override
  void dispose() {
    for (final recognizer in _recognizers) {
      recognizer.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    for (final recognizer in _recognizers) {
      recognizer.dispose();
    }
    _recognizers.clear();
    final links = <String, String>{};
    for (final source in widget.sources.where(
      (item) => item['type'] == 'internal',
    )) {
      final webUrl = source['url'];
      final mobileRoute = source['mobile_route'];
      if (webUrl is String &&
          mobileRoute is String &&
          webUrl.startsWith('/') &&
          !webUrl.startsWith('//')) {
        links[webUrl] = mobileRoute;
      }
    }

    final spans = <InlineSpan>[];
    final pattern = RegExp(r'\[([^\]]+)\]\(([^)]+)\)');
    var cursor = 0;
    for (final match in pattern.allMatches(widget.text)) {
      if (match.start > cursor) {
        spans.add(TextSpan(text: widget.text.substring(cursor, match.start)));
      }
      final label = match.group(1) ?? '';
      final route = links[match.group(2)];
      TapGestureRecognizer? recognizer;
      if (route != null && widget.onOpenLink != null) {
        recognizer = TapGestureRecognizer()
          ..onTap = () => widget.onOpenLink!(route);
        _recognizers.add(recognizer);
      }
      spans.add(
        TextSpan(
          text: label,
          style: recognizer == null
              ? null
              : const TextStyle(
                  color: TkColors.violet,
                  decoration: TextDecoration.underline,
                ),
          recognizer: recognizer,
        ),
      );
      cursor = match.end;
    }
    if (cursor < widget.text.length) {
      spans.add(TextSpan(text: widget.text.substring(cursor)));
    }

    return RichText(
      text: TextSpan(style: widget.style, children: spans),
    );
  }
}
