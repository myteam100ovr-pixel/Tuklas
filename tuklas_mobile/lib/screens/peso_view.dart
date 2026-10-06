import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class PesoView extends StatefulWidget {
  const PesoView({super.key});

  @override
  State<PesoView> createState() => _PesoViewState();
}

class _PesoViewState extends State<PesoView> {
  late Future<List<dynamic>> _matches;

  @override
  void initState() {
    super.initState();
    _matches = ApiClient.instance.pesoMatches();
  }

  Future<void> _reload() async {
    final request = ApiClient.instance.pesoMatches();
    setState(() => _matches = request);
    await request;
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);

    return FutureBuilder<List<dynamic>>(
      future: _matches,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(
            child: CircularProgressIndicator(color: TkColors.violet),
          );
        }

        if (snapshot.hasError) {
          final message = snapshot.error is ApiException
              ? (snapshot.error as ApiException).message
              : 'Could not load PESO job matches. Check your connection and retry.';
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(message, textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  OutlinedButton.icon(
                    onPressed: _reload,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Try again'),
                  ),
                ],
              ),
            ),
          );
        }

        final matches = snapshot.data ?? [];
        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 900),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'PESO job matches',
                      style: tkText(context, size: 27, weight: 500, spacing: -0.7),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Career suggestions based on scanned resumes and certificates.',
                      style: tkText(context, size: 15, color: colors.muted),
                    ),
                    const SizedBox(height: 16),
                    if (matches.isEmpty)
                      TkCard(
                        child: Text(
                          'No job matches yet. Scan a resume or certificate to get personalized career guidance.',
                          style: tkText(context, size: 14, color: colors.muted),
                        ),
                      ),
                    for (final raw in matches) ...[
                      const SizedBox(height: 12),
                      _matchCard(context, asMap(raw)),
                    ],
                    const SizedBox(height: 16),
                    Text(
                      'Suggestions are guidance, not guarantees of employment.',
                      style: tkText(context, size: 12, color: colors.muted),
                    ),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _matchCard(BuildContext context, Map<String, dynamic> match) {
    final jobs = asList(match['job_recommendations']);
    final gaps = asList(match['skill_gaps']);
    final resources = asList(match['learning_recommendations']);

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (match['name'] != null)
            Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Text(
                '${match['name']}',
                style: tkText(context, size: 13, color: TkColors.violet),
              ),
            ),
          Text(
            '${match['original_name'] ?? 'Scanned document'}',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: tkText(context, size: 16, weight: 500),
          ),
          _section(context, 'Suggested jobs', jobs),
          _section(context, 'Skills to build', gaps),
          _section(context, 'Free learning and practice', resources),
        ],
      ),
    );
  }

  Widget _section(BuildContext context, String title, List<dynamic> items) {
    if (items.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(top: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: tkText(context, size: 13.5, weight: 500)),
          const SizedBox(height: 5),
          for (final item in items) _recommendation(context, item),
        ],
      ),
    );
  }

  Widget _recommendation(BuildContext context, dynamic item) {
    final value = asMap(item);
    final title = value['title'] ?? value['name'] ?? item;
    final details = [
      if (value['match'] != null) '${value['match']} match',
      if (value['reason'] != null) '${value['reason']}',
      if (value['description'] != null) '${value['description']}',
      if (value['evidence'] != null) 'Evidence: ${value['evidence']}',
    ];
    final url = value['directUrl'] ?? value['url'];
    final uri = url is String ? Uri.tryParse(url) : null;
    final safeUri = uri != null && uri.scheme == 'https' && uri.host.isNotEmpty
        ? uri
        : null;

    return Padding(
      padding: const EdgeInsets.only(top: 3, bottom: 3),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('• $title', style: tkText(context, size: 13)),
          for (final detail in details)
            Padding(
              padding: const EdgeInsets.only(left: 12, top: 2),
              child: Text(
                detail,
                style: tkText(
                  context,
                  size: 12,
                  color: TkColors.of(context).muted,
                ),
              ),
            ),
          if (safeUri != null)
            TextButton.icon(
              onPressed: () async {
                final opened = await launchUrl(
                  safeUri,
                  mode: LaunchMode.externalApplication,
                );
                if (!opened && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('Could not open this resource.')),
                  );
                }
              },
              icon: const Icon(Icons.open_in_new, size: 14),
              label: const Text('Open resource'),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 4),
                visualDensity: VisualDensity.compact,
              ),
            ),
        ],
      ),
    );
  }
}
