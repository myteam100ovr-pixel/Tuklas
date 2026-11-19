import 'package:flutter/material.dart';

import '../api.dart';
import '../date_format.dart';

class PatchNotesView extends StatefulWidget {
  const PatchNotesView({super.key});

  @override
  State<PatchNotesView> createState() => _PatchNotesViewState();
}

class _PatchNotesViewState extends State<PatchNotesView> {
  List<Map<String, dynamic>> _releases = [];
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
      final result = await ApiClient.instance.workspace(
        '/patch-notes',
        auth: false,
      );
      final raw = result['patch_notes'];
      final releases = raw is List
          ? raw
                .whereType<Map>()
                .map((entry) => Map<String, dynamic>.from(entry))
                .toList()
          : <Map<String, dynamic>>[];
      releases.sort(
        (left, right) =>
            '${right['published_at']}'.compareTo('${left['published_at']}'),
      );
      if (mounted) setState(() => _releases = releases);
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Could not load patch notes. Check your connection and retry.',
        );
      }
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Patch Notes')),
    body: RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Text(
            'Website and mobile app updates, newest first.',
            style: Theme.of(context).textTheme.bodyMedium,
          ),
          const SizedBox(height: 12),
          if (_loading && _releases.isEmpty)
            const Padding(
              padding: EdgeInsets.all(28),
              child: Center(child: CircularProgressIndicator()),
            ),
          if (_error != null) ...[
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            Align(
              alignment: Alignment.centerLeft,
              child: TextButton.icon(
                onPressed: _load,
                icon: const Icon(Icons.refresh),
                label: const Text('Try again'),
              ),
            ),
          ],
          for (final release in _releases)
            Card(
              margin: const EdgeInsets.only(bottom: 12),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Wrap(
                      spacing: 8,
                      runSpacing: 4,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        Text(formatTuklasDate(release['date'])),
                        Chip(label: Text('${release['version'] ?? 'Update'}')),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${release['title'] ?? 'Tuklas update'}',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 8),
                    for (final change
                        in (release['items'] as List? ?? const []))
                      Padding(
                        padding: const EdgeInsets.only(bottom: 6),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('•  '),
                            Expanded(child: Text('$change')),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            ),
          if (!_loading && _error == null && _releases.isEmpty)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Text('No updates have been published yet.'),
            ),
        ],
      ),
    ),
  );
}
