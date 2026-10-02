import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import 'tuklas_api.dart';
import 'tuklas_theme.dart';

class TuklasDashboardScreen extends StatefulWidget {
  const TuklasDashboardScreen({
    super.key,
    required this.api,
    required this.onNavigate,
    required this.onOpenChat,
  });

  final TuklasApi api;
  final ValueChanged<String> onNavigate;
  final VoidCallback onOpenChat;

  @override
  State<TuklasDashboardScreen> createState() => _TuklasDashboardScreenState();
}

class _TuklasDashboardScreenState extends State<TuklasDashboardScreen> {
  Map<String, dynamic>? _dashboard;
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final result = await widget.api.dashboard();
      if (mounted) setState(() => _dashboard = result);
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading && _dashboard == null) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _dashboard == null) {
      return _LoadError(message: _error!, onRetry: _load);
    }

    final data = _dashboard ?? const <String, dynamic>{};
    final stats = List<Map<String, dynamic>>.from(data['stats'] ?? const []);
    final chart = Map<String, dynamic>.from(data['chart'] ?? const {});
    final tasks = List<Map<String, dynamic>>.from(data['tasks'] ?? const []);
    final recent = List<Map<String, dynamic>>.from(data['recent'] ?? const []);

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        key: const PageStorageKey('dashboard'),
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 32),
        children: [
          Text(
            _eyebrow(data['role'] as String? ?? ''),
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: TuklasColors.violet,
                  fontWeight: FontWeight.w700,
                ),
          ),
          const SizedBox(height: 7),
          Text(
            '${data['title'] ?? 'Overview'}',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                  fontWeight: FontWeight.w500,
                ),
          ),
          const SizedBox(height: 16),
          LayoutBuilder(
            builder: (context, constraints) {
              final width = (constraints.maxWidth - 10) / 2;
              return Wrap(
                spacing: 10,
                runSpacing: 10,
                children: [
                  for (final stat in stats)
                    SizedBox(width: width, child: _StatCard(stat: stat)),
                ],
              );
            },
          ),
          const SizedBox(height: 18),
          _DashboardPanel(
            title: '${chart['title'] ?? 'Overview'}',
            trailing: _chartRange(data['role'] as String? ?? ''),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (chart['kind'] == 'bars')
                  _TrendChart(items: List<Map<String, dynamic>>.from(chart['items'] ?? const []))
                else
                  _BreakdownChart(items: List<Map<String, dynamic>>.from(chart['items'] ?? const [])),
                if (chart['insight'] is String) ...[
                  const SizedBox(height: 12),
                  Text(
                    chart['insight'] as String,
                    style: Theme.of(context).textTheme.bodySmall?.copyWith(
                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                          height: 1.45,
                        ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 18),
          _SectionHeading(title: _taskHeading(data['role'] as String? ?? ''), trailing: 'TODAY'),
          const SizedBox(height: 6),
          for (final task in tasks)
            _TaskTile(
              task: task,
              onTap: task['destination'] is String
                  ? () => widget.onNavigate(task['destination'] as String)
                  : null,
            ),
          if (recent.isNotEmpty) ...[
            const SizedBox(height: 18),
            _SectionHeading(title: _recentHeading(data['role'] as String? ?? ''), trailing: 'LATEST'),
            const SizedBox(height: 6),
            _DashboardPanel(
              title: _recentHeading(data['role'] as String? ?? ''),
              child: Column(
                children: [
                  for (var index = 0; index < recent.length; index++) ...[
                    if (index > 0) const Divider(height: 14),
                    _RecentRow(item: recent[index]),
                  ],
                ],
              ),
            ),
          ],
          const SizedBox(height: 18),
          _CareerAssistantBanner(onTap: widget.onOpenChat),
        ],
      ),
    );
  }
}

String _eyebrow(String role) => switch (role) {
      'super_admin' => 'PESO BUGALLON · OVERVIEW',
      'trainer' => 'TESDA LINGAYEN · TRAINER',
      _ => 'YOUR NEXT STEP · YOUTH',
    };

String _chartRange(String role) => role == 'super_admin' ? 'LAST 14 DAYS' : role == 'trainer' ? 'ALL PROGRAMS' : 'RIGHT NOW';

String _taskHeading(String role) => role == 'trainer' ? 'Workspace checklist' : role == 'super_admin' ? 'Setup checklist' : 'Your next steps';

String _recentHeading(String role) => role == 'trainer' ? 'Recent programs' : role == 'super_admin' ? 'Latest youth accounts' : 'Recent scans';

class _StatCard extends StatelessWidget {
  const _StatCard({required this.stat});

  final Map<String, dynamic> stat;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      constraints: const BoxConstraints(minHeight: 94),
      padding: const EdgeInsets.all(13),
      decoration: BoxDecoration(
        color: Theme.of(context).cardTheme.color,
        border: Border.all(color: colors.outline),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('${stat['label'] ?? ''}', style: Theme.of(context).textTheme.bodySmall?.copyWith(color: colors.onSurfaceVariant)),
          const SizedBox(height: 6),
          Text('${stat['value'] ?? '0'}', style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w600)),
          if (stat['note'] is String) ...[
            const SizedBox(height: 2),
            Text('${stat['note']}', maxLines: 2, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: colors.onSurfaceVariant)),
          ],
        ],
      ),
    );
  }
}

class _DashboardPanel extends StatelessWidget {
  const _DashboardPanel({required this.title, required this.child, this.trailing});

  final String title;
  final String? trailing;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Theme.of(context).cardTheme.color,
        border: Border.all(color: colors.outline),
        borderRadius: BorderRadius.circular(13),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600))),
              if (trailing != null) Text(trailing!, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: TuklasColors.violet)),
            ],
          ),
          const SizedBox(height: 13),
          child,
        ],
      ),
    );
  }
}

class _BreakdownChart extends StatelessWidget {
  const _BreakdownChart({required this.items});

  final List<Map<String, dynamic>> items;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;
    return Column(
      children: [
        for (final item in items) ...[
          Row(
            children: [
              Expanded(child: Text('${item['label'] ?? ''}', style: Theme.of(context).textTheme.bodySmall)),
              Text('${item['pct'] ?? 0}%', style: Theme.of(context).textTheme.labelMedium?.copyWith(color: muted)),
            ],
          ),
          const SizedBox(height: 5),
          ClipRRect(
            borderRadius: BorderRadius.circular(99),
            child: LinearProgressIndicator(
              value: ((item['pct'] as num?)?.toDouble() ?? 0) / 100,
              minHeight: 7,
              backgroundColor: Theme.of(context).colorScheme.surfaceContainerHighest,
              color: TuklasColors.pink,
            ),
          ),
          const SizedBox(height: 10),
        ],
      ],
    );
  }
}

class _TrendChart extends StatelessWidget {
  const _TrendChart({required this.items});

  final List<Map<String, dynamic>> items;

  @override
  Widget build(BuildContext context) {
    final values = items.map((item) => (item['value'] as num?)?.toDouble() ?? 0).toList();
    final maximum = values.fold<double>(1, (current, value) => value > current ? value : current);
    return SizedBox(
      height: 176,
      child: BarChart(
        BarChartData(
          maxY: maximum + 1,
          minY: 0,
          gridData: const FlGridData(show: false),
          borderData: FlBorderData(show: false),
          titlesData: const FlTitlesData(show: false),
          barTouchData: BarTouchData(
            enabled: true,
            touchTooltipData: BarTouchTooltipData(
              getTooltipItem: (group, groupIndex, rod, rodIndex) {
                final label = groupIndex < items.length ? '${items[groupIndex]['label']}' : '';
                return BarTooltipItem('$label\n${rod.toY.toInt()}', const TextStyle(color: Colors.white, fontSize: 11));
              },
            ),
          ),
          barGroups: [
            for (var index = 0; index < values.length; index++)
              BarChartGroupData(
                x: index,
                barRods: [
                  BarChartRodData(
                    toY: values[index],
                    width: 11,
                    color: index.isEven ? TuklasColors.violet : TuklasColors.pink,
                    borderRadius: BorderRadius.circular(3),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }
}

class _SectionHeading extends StatelessWidget {
  const _SectionHeading({required this.title, required this.trailing});

  final String title;
  final String trailing;

  @override
  Widget build(BuildContext context) => Row(
        children: [
          Expanded(child: Text(title, style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500))),
          Text(trailing, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: TuklasColors.violet, fontWeight: FontWeight.w700)),
        ],
      );
}

class _TaskTile extends StatelessWidget {
  const _TaskTile({required this.task, required this.onTap});

  final Map<String, dynamic> task;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final done = task['done'] == true;
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(horizontal: 3),
      leading: Icon(done ? Icons.check_circle : Icons.circle_outlined, color: done ? TuklasColors.good : TuklasColors.violet, size: 22),
      title: Text('${task['title'] ?? ''}', style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500)),
      subtitle: Text('${task['meta'] ?? ''}', style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
      trailing: onTap == null ? null : const Icon(Icons.chevron_right),
      onTap: onTap,
    );
  }
}

class _RecentRow extends StatelessWidget {
  const _RecentRow({required this.item});

  final Map<String, dynamic> item;

  @override
  Widget build(BuildContext context) {
    final status = '${item['status'] ?? ''}';
    final badgeColor = switch (status) {
      'done' || 'published' || 'verified' => TuklasColors.good,
      'failed' || 'unverified' => Theme.of(context).colorScheme.error,
      _ => Theme.of(context).colorScheme.onSurfaceVariant,
    };
    return Row(
      children: [
        CircleAvatar(
          radius: 17,
          backgroundColor: TuklasColors.violet.withValues(alpha: .12),
          foregroundColor: TuklasColors.violet,
          child: Text(_initials('${item['primary'] ?? ''}'), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('${item['primary'] ?? ''}', maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w500)),
              Text('${item['secondary'] ?? item['date'] ?? ''}', maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
            ],
          ),
        ),
        const SizedBox(width: 8),
        Text(status, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: badgeColor)),
      ],
    );
  }
}

class _CareerAssistantBanner extends StatelessWidget {
  const _CareerAssistantBanner({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: TuklasColors.deep,
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              const Icon(Icons.auto_awesome, color: TuklasColors.yellow, size: 25),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Google Gemini', style: TextStyle(color: Colors.white70, fontSize: 11)),
                    SizedBox(height: 3),
                    Text('How can I help you?', style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w500)),
                  ],
                ),
              ),
              const Icon(Icons.arrow_forward, color: Colors.white),
            ],
          ),
        ),
      ),
    );
  }
}

class _LoadError extends StatelessWidget {
  const _LoadError({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off_outlined, size: 38, color: TuklasColors.violet),
              const SizedBox(height: 12),
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              OutlinedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('Try again')),
            ],
          ),
        ),
      );
}

String _initials(String value) {
  final parts = value.trim().split(RegExp(r'\s+')).where((part) => part.isNotEmpty).take(2);
  return parts.map((part) => part.substring(0, 1).toUpperCase()).join();
}
