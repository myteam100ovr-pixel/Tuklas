import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class DashboardView extends StatefulWidget {
  const DashboardView({
    super.key,
    required this.onEditProfile,
    required this.onOpenPeso,
    required this.onOpenScanner,
    required this.onOpenTesda,
    required this.onLogout,
  });

  final VoidCallback onEditProfile;
  final VoidCallback onOpenPeso;
  final VoidCallback onOpenScanner;
  final VoidCallback onOpenTesda;
  final VoidCallback onLogout;

  @override
  State<DashboardView> createState() => _DashboardViewState();
}

Widget _insightItem(BuildContext context, dynamic item) {
  if (item is! Map) {
    return Text('• $item', style: tkText(context, size: 13.5));
  }
  final title = item['title'] ?? item['name'] ?? 'Recommendation';
  final reason = item['reason'] ?? item['description'];
  final evidence = item['evidence'];

  return Text(
    '• $title${reason == null ? '' : ' — $reason'}'
    '${evidence == null ? '' : ' (Evidence: $evidence)'}',
    style: tkText(context, size: 13.5),
  );
}

class _DashboardViewState extends State<DashboardView> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = ApiClient.instance.dashboard();
  }

  Future<void> _reload() async {
    final next = ApiClient.instance.dashboard();
    setState(() => _future = next);
    try {
      await next;
    } catch (_) {
      // The error is shown by the FutureBuilder below.
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(
            child: CircularProgressIndicator(color: TkColors.violet),
          );
        }
        if (snapshot.hasError) {
          return _ErrorView(
            error: snapshot.error!,
            onRetry: _reload,
            onLogout: widget.onLogout,
          );
        }
        return RefreshIndicator(
          color: TkColors.violet,
          onRefresh: _reload,
          child: _page(context, snapshot.data!),
        );
      },
    );
  }

  Widget _page(BuildContext context, Map<String, dynamic> data) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
      children: [
        Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 1500),
            child: LayoutBuilder(
              builder: (context, box) {
                final wide = box.maxWidth >= 1100;
                final leftWidth = wide ? box.maxWidth - 376 - 16 : box.maxWidth;

                final title = Padding(
                  padding: const EdgeInsets.only(top: 4, bottom: 18),
                  child: Text(
                    '${data['title'] ?? ''}',
                    style: tkText(
                      context,
                      size: 27,
                      weight: 500,
                      spacing: -0.7,
                    ),
                  ),
                );

                final left = Column(
                  children: [
                    _stats(context, asList(data['stats']), leftWidth >= 640),
                    const SizedBox(height: 16),
                    _chartCard(context, asMap(data['chart']), leftWidth),
                    if (data['profileInsights'] is Map) ...[
                      const SizedBox(height: 16),
                      _profileInsightsCard(
                        context,
                        asMap(data['profileInsights']),
                      ),
                    ],
                    const SizedBox(height: 16),
                    _tableCard(context, asMap(data['table'])),
                  ],
                );

                final right = Column(
                  children: [
                    _tasksCard(context, asMap(data['tasks'])),
                    const SizedBox(height: 16),
                    _quickCard(context, asList(data['actions'])),
                    const SizedBox(height: 16),
                    const _CareerChatCard(),
                  ],
                );

                if (wide) {
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      title,
                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Expanded(child: left),
                          const SizedBox(width: 16),
                          SizedBox(width: 376, child: right),
                        ],
                      ),
                    ],
                  );
                }
                return Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [title, left, const SizedBox(height: 16), right],
                );
              },
            ),
          ),
        ),
      ],
    );
  }

  /* ---------------- stat cards ---------------- */

  Widget _stats(BuildContext context, List<dynamic> stats, bool row) {
    final cards = [for (final s in stats) _statCard(context, asMap(s))];

    if (!row) {
      return Column(
        children: [
          for (var i = 0; i < cards.length; i++)
            Padding(
              padding: EdgeInsets.only(bottom: i == cards.length - 1 ? 0 : 16),
              child: cards[i],
            ),
        ],
      );
    }
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (var i = 0; i < cards.length; i++) ...[
            if (i > 0) const SizedBox(width: 16),
            Expanded(child: cards[i]),
          ],
        ],
      ),
    );
  }

  Widget _statCard(BuildContext context, Map<String, dynamic> s) {
    final c = TkColors.of(context);
    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Flexible(
                child: Text(
                  '${s['label'] ?? ''}',
                  style: tkText(context, size: 15, weight: 500),
                ),
              ),
              if (s['delta'] != null)
                TkChip(label: '${s['delta']}', tone: '${s['tone']}'),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                '${s['value'] ?? ''}',
                style: tkText(
                  context,
                  size: 35,
                  spacing: -1.2,
                  height: 1,
                  tabular: true,
                ),
              ),
              Flexible(
                child: Padding(
                  padding: const EdgeInsets.only(left: 12),
                  child: Text(
                    '${s['note'] ?? ''}',
                    textAlign: TextAlign.right,
                    style: tkText(
                      context,
                      size: 11.5,
                      color: c.muted,
                      height: 1.3,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /* ---------------- chart card ---------------- */

  Color _tone(dynamic tone) {
    switch ('$tone') {
      case 'pink':
        return TkColors.pink;
      case 'yellow':
        return TkColors.yellow;
      default:
        return TkColors.violet;
    }
  }

  Widget _cardHead(BuildContext context, String title, {String? chip}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text(
              title,
              style: tkText(context, size: 17, weight: 500, spacing: -0.2),
            ),
          ),
          if (chip != null) TkChip(label: chip),
        ],
      ),
    );
  }

  Widget _chartCard(
    BuildContext context,
    Map<String, dynamic> chart,
    double width,
  ) {
    final c = TkColors.of(context);

    final side = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final item in asList(chart['legend']))
          Padding(
            padding: const EdgeInsets.only(bottom: 8),
            child: Row(
              children: [
                Container(
                  width: 9,
                  height: 9,
                  decoration: BoxDecoration(
                    color: _tone(asMap(item)['tone']),
                    shape: BoxShape.circle,
                  ),
                ),
                const SizedBox(width: 10),
                Flexible(
                  child: Text(
                    '${asMap(item)['label'] ?? ''}',
                    style: tkText(context, size: 13.5),
                  ),
                ),
              ],
            ),
          ),
        const SizedBox(height: 8),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: c.surface2,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Text(
            '${chart['insight'] ?? ''}',
            style: tkText(context, size: 12.8, color: c.ink2, height: 1.5),
          ),
        ),
        if (chart['cta'] != null) ...[
          const SizedBox(height: 16),
          SizedBox(
            width: double.infinity,
            child: FilledButton(
              onPressed: chart['cta']['href'] == 'scanner'
                  ? widget.onOpenScanner
                  : chart['cta']['href'] == 'tesda'
                  ? widget.onOpenTesda
                  : widget.onEditProfile,
              style: FilledButton.styleFrom(
                backgroundColor: TkColors.violet,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(13),
                ),
              ),
              child: Text(
                '${asMap(chart['cta'])['label'] ?? ''}',
                style: tkText(
                  context,
                  size: 14,
                  weight: 500,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ],
      ],
    );

    Widget main;
    if ('${chart['kind']}' == 'bars') {
      final bars = asList(chart['bars']);
      final axis = asList(chart['axis']);
      main = Column(
        children: [
          Container(
            height: 150,
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: c.line)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                for (final b in bars)
                  Expanded(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 1.5),
                      child: Align(
                        alignment: Alignment.bottomCenter,
                        child: FractionallySizedBox(
                          heightFactor:
                              ((asMap(b)['h'] as num?) ?? 4).toDouble().clamp(
                                2,
                                100,
                              ) /
                              100,
                          child: Container(
                            decoration: const BoxDecoration(
                              borderRadius: BorderRadius.vertical(
                                top: Radius.circular(7),
                                bottom: Radius.circular(2),
                              ),
                              gradient: LinearGradient(
                                begin: Alignment.topCenter,
                                end: Alignment.bottomCenter,
                                colors: [TkColors.violet, TkColors.pink],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
              ],
            ),
          ),
          const SizedBox(height: 7),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                axis.isNotEmpty ? '${axis.first}' : '',
                style: tkText(context, size: 11.5, color: c.muted),
              ),
              Text(
                axis.length > 1 ? '${axis.last}' : '',
                style: tkText(context, size: 11.5, color: c.muted),
              ),
            ],
          ),
        ],
      );
    } else {
      main = Column(
        children: [
          for (final item in asList(chart['items']))
            Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Column(
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        '${asMap(item)['label'] ?? ''}',
                        style: tkText(context, size: 13.5),
                      ),
                      Text(
                        '${asMap(item)['pct'] ?? 0}%',
                        style: tkText(
                          context,
                          size: 13.5,
                          weight: 500,
                          tabular: true,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 7),
                  Container(
                    height: 9,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: c.surface2,
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: FractionallySizedBox(
                      alignment: Alignment.centerLeft,
                      widthFactor:
                          (((asMap(item)['pct'] as num?) ?? 0).toDouble().clamp(
                            0,
                            100,
                          )) /
                          100,
                      child: Container(
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(999),
                          gradient: const LinearGradient(
                            colors: [TkColors.violet, TkColors.pink],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      );
    }

    final body = width >= 700
        ? IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SizedBox(
                  width: 216,
                  child: Align(alignment: Alignment.topLeft, child: side),
                ),
                const SizedBox(width: 26),
                Expanded(
                  child: Align(alignment: Alignment.bottomCenter, child: main),
                ),
              ],
            ),
          )
        : Column(children: [side, const SizedBox(height: 20), main]);

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHead(
            context,
            '${chart['title'] ?? ''}',
            chip: '${chart['range'] ?? ''}',
          ),
          body,
        ],
      ),
    );
  }

  /* ---------------- table card ---------------- */

  Widget _tableCard(BuildContext context, Map<String, dynamic> table) {
    final c = TkColors.of(context);
    final columns = asList(table['columns']).map((e) => '$e').toList();
    final rows = asList(table['rows']);

    Widget body;
    if (rows.isEmpty) {
      body = Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 30),
        decoration: BoxDecoration(
          border: Border.all(color: c.line),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            Icon(Icons.format_list_bulleted, color: c.muted),
            const SizedBox(height: 8),
            Text(
              '${table['empty'] ?? ''}',
              textAlign: TextAlign.center,
              style: tkText(context, size: 14, color: c.muted),
            ),
          ],
        ),
      );
    } else {
      body = LayoutBuilder(
        builder: (context, box) {
          const base = <double>[230, 120, 120, 110];
          final total = base.fold<double>(0, (a, b) => a + b);
          final k = box.maxWidth > total ? box.maxWidth / total : 1.0;
          final widths = base.map((w) => w * k).toList();

          Widget headerRow = Row(
            children: [
              for (var i = 0; i < columns.length && i < widths.length; i++)
                SizedBox(
                  width: widths[i],
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(10, 0, 10, 10),
                    child: Text(
                      columns[i],
                      style: tkText(context, size: 11.5, color: c.muted),
                    ),
                  ),
                ),
            ],
          );

          return SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Column(
              children: [
                headerRow,
                for (final row in rows)
                  Container(
                    decoration: BoxDecoration(
                      border: Border(top: BorderSide(color: c.line)),
                    ),
                    child: Row(
                      children: [
                        for (
                          var i = 0;
                          i < asList(row).length && i < widths.length;
                          i++
                        )
                          SizedBox(
                            width: widths[i],
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 10,
                                vertical: 13,
                              ),
                              child: _cell(context, asMap(asList(row)[i])),
                            ),
                          ),
                      ],
                    ),
                  ),
              ],
            ),
          );
        },
      );
    }

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [_cardHead(context, '${table['title'] ?? ''}'), body],
      ),
    );
  }

  Widget _profileInsightsCard(
    BuildContext context,
    Map<String, dynamic> insights,
  ) {
    final groups = <(String, String)>[
      ('skills', 'Skills'),
      ('credentials', 'Qualifications and certificates'),
      ('job_roles', 'Suggested job roles'),
      ('tesda_training', 'TESDA training to consider'),
      ('job_recommendations', 'PESO job matches'),
      ('skill_gaps', 'Skills to develop'),
      ('learning_recommendations', 'Free learning and practice'),
    ];

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHead(context, 'Your career insights'),
          for (final (key, title) in groups) ...[
            Padding(
              padding: const EdgeInsets.only(top: 8, bottom: 4),
              child: Text(title, style: tkText(context, size: 14, weight: 500)),
            ),
            for (final item in asList(insights[key]))
              Padding(
                padding: const EdgeInsets.only(left: 4, top: 3, bottom: 3),
                child: _insightItem(context, item),
              ),
            if (asList(insights[key]).isEmpty)
              Text(
                'Nothing saved yet.',
                style: tkText(
                  context,
                  size: 12.5,
                  color: TkColors.of(context).muted,
                ),
              ),
          ],
          const SizedBox(height: 8),
          Text(
            'Suggestions are guidance, not guarantees of jobs, admission, or training slots.',
            style: tkText(
              context,
              size: 12,
              color: TkColors.of(context).muted,
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }

  Widget _cell(BuildContext context, Map<String, dynamic> cell) {
    final c = TkColors.of(context);

    if (cell['chip'] != null) {
      return Align(
        alignment: Alignment.centerLeft,
        child: TkChip(
          label: '${cell['chip']}',
          tone: '${cell['tone'] ?? 'neutral'}',
        ),
      );
    }

    final text = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          '${cell['t'] ?? ''}',
          maxLines: 1,
          overflow: TextOverflow.ellipsis,
          style: tkText(context, size: 14),
        ),
        if (cell['s'] != null)
          Text(
            '${cell['s']}',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: tkText(context, size: 11.5, color: c.muted),
          ),
      ],
    );

    if (cell['avatar'] == null) return text;

    return Row(
      children: [
        Container(
          width: 38,
          height: 38,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: c.surface2, shape: BoxShape.circle),
          child: Text(
            '${cell['avatar']}',
            style: tkText(context, size: 11.5, weight: 500),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(child: text),
      ],
    );
  }

  /* ---------------- tasks and quick actions ---------------- */

  Widget _tasksCard(BuildContext context, Map<String, dynamic> tasks) {
    final c = TkColors.of(context);
    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHead(context, '${tasks['title'] ?? ''}'),
          for (final raw in asList(tasks['items']))
            Builder(
              builder: (context) {
                final t = asMap(raw);
                final done = '${t['state']}' == 'done';
                return Padding(
                  padding: const EdgeInsets.symmetric(
                    vertical: 10,
                    horizontal: 4,
                  ),
                  child: InkWell(
                    onTap: switch (t['href']) {
                      'profile' => widget.onEditProfile,
                      'scanner' => widget.onOpenScanner,
                      'peso' => widget.onOpenPeso,
                      'tesda' => widget.onOpenTesda,
                      _ => null,
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 24,
                          height: 24,
                          margin: const EdgeInsets.only(top: 2),
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: done ? TkColors.violet : Colors.transparent,
                            border: Border.all(
                              color: TkColors.violet,
                              width: 2,
                            ),
                          ),
                          child: done
                              ? const Icon(
                                  Icons.check,
                                  size: 14,
                                  color: Colors.white,
                                )
                              : null,
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                '${t['title'] ?? ''}',
                                style: tkText(context, size: 14.7, weight: 500),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '${t['meta'] ?? ''}',
                                style: tkText(
                                  context,
                                  size: 11.5,
                                  color: c.taskMeta,
                                ),
                              ),
                              const SizedBox(height: 5),
                              Text(
                                '${t['desc'] ?? ''}',
                                style: tkText(
                                  context,
                                  size: 12.5,
                                  color: c.muted,
                                  height: 1.4,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  Widget _quickCard(BuildContext context, List<dynamic> actions) {
    final c = TkColors.of(context);
    final tileColors = [
      TkColors.violet,
      TkColors.pink,
      TkColors.orange,
      TkColors.violet,
    ];

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _cardHead(context, 'Quick actions'),
          LayoutBuilder(
            builder: (context, box) {
              final tileWidth = (box.maxWidth - 11) / 2;
              return Wrap(
                spacing: 11,
                runSpacing: 11,
                children: [
                  for (var i = 0; i < actions.length; i++)
                    Builder(
                      builder: (context) {
                        final a = asMap(actions[i]);
                        final label = '${a['label']}';
                        final VoidCallback? onTap = switch (a['href']) {
                          'profile' => widget.onEditProfile,
                          'scanner' => widget.onOpenScanner,
                          'peso' => widget.onOpenPeso,
                          'tesda' => widget.onOpenTesda,
                          _ => null,
                        };
                        final enabled = onTap != null;
                        return Opacity(
                          opacity: enabled ? 1 : 0.6,
                          child: InkWell(
                            onTap: onTap,
                            borderRadius: BorderRadius.circular(18),
                            child: Container(
                              width: tileWidth,
                              height: 100,
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: c.surface2,
                                borderRadius: BorderRadius.circular(18),
                                border: Border.all(color: c.line),
                              ),
                              child: Stack(
                                children: [
                                  Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      Container(
                                        width: 34,
                                        height: 34,
                                        decoration: BoxDecoration(
                                          color: c.surface,
                                          borderRadius: BorderRadius.circular(
                                            11,
                                          ),
                                        ),
                                        child: Icon(
                                          tkIcon('${a['icon']}'),
                                          size: 18,
                                          color:
                                              tileColors[i % tileColors.length],
                                        ),
                                      ),
                                      Text(
                                        label,
                                        style: tkText(context, size: 13.8),
                                      ),
                                    ],
                                  ),
                                  if (!enabled)
                                    const Positioned(
                                      top: 0,
                                      right: 0,
                                      child: TkChip(label: 'Soon'),
                                    ),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
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

class _CareerChatCard extends StatefulWidget {
  const _CareerChatCard();

  @override
  State<_CareerChatCard> createState() => _CareerChatCardState();
}

class _CareerChatCardState extends State<_CareerChatCard> {
  final _messageController = TextEditingController();
  final _messages = <Map<String, String>>[];
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _send([String? prompt]) async {
    final text = (prompt ?? _messageController.text).trim();
    if (text.isEmpty || _sending) return;

    _messageController.clear();
    final history = _messages.skip(
      (_messages.length - 9).clamp(0, _messages.length),
    );
    final requestMessages = [
      ...history.map((message) => Map<String, String>.from(message)),
      {'role': 'user', 'text': text},
    ];
    setState(() {
      _messages.add({'role': 'user', 'text': text});
      if (_messages.length > 20) {
        _messages.removeRange(0, _messages.length - 20);
      }
      _sending = true;
      _error = null;
    });

    try {
      final reply = await ApiClient.instance.careerChat(requestMessages);
      if (!mounted) return;
      setState(() {
        _messages.add({'role': 'model', 'text': reply});
        if (_messages.length > 20) {
          _messages.removeRange(0, _messages.length - 20);
        }
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error is ApiException ? error.message : 'Could not reach the career assistant. Check your connection and try again.';
      });
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);
    final suggestions = [
      'Explore careers',
      'Find skills training',
      'Plan a next step',
      'Build my skills',
    ];
    final prompts = [
      'Help me explore careers that match my interests.',
      'How can I find skills training that fits my goals?',
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
            'Ask Tuklas about careers, skills training, or a practical next step.',
            style: tkText(context, size: 13, color: colors.muted, height: 1.4),
          ),
          const SizedBox(height: 12),
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
                        child: Text(
                          message['text']!,
                          style: tkText(
                            context,
                            size: 13,
                            color: message['role'] == 'user'
                                ? Colors.white
                                : colors.ink,
                            height: 1.4,
                          ),
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
                  onPressed: _sending ? null : () => _send(prompts[i]),
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
                  enabled: !_sending,
                  decoration: const InputDecoration(
                    hintText: 'Ask something...',
                    counterText: '',
                  ),
                  onSubmitted: (_) => _send(),
                ),
              ),
              const SizedBox(width: 8),
              IconButton.filled(
                onPressed: _sending ? null : _send,
                tooltip: 'Send message',
                icon: const Icon(Icons.arrow_upward),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            'Messages are sent to Google Gemini. Avoid sharing private information.',
            style: tkText(context, size: 11.5, color: colors.muted),
          ),
        ],
      ),
    );
  }
}

class _ErrorView extends StatelessWidget {
  const _ErrorView({
    required this.error,
    required this.onRetry,
    required this.onLogout,
  });

  final Object error;
  final Future<void> Function() onRetry;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final message = error is ApiException
        ? (error as ApiException).message
        : 'Could not reach the server. Check the address and your connection.';
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 420),
          child: TkCard(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: tkText(context, size: 14.5),
                ),
                const SizedBox(height: 18),
                FilledButton(
                  onPressed: onRetry,
                  style: FilledButton.styleFrom(
                    backgroundColor: TkColors.violet,
                    foregroundColor: Colors.white,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(13),
                    ),
                  ),
                  child: const Text('Try again'),
                ),
                TextButton(
                  onPressed: onLogout,
                  child: const Text('Back to login'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
