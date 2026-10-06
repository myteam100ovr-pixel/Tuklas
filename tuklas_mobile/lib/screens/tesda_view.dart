import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class TesdaView extends StatefulWidget {
  const TesdaView({super.key});

  @override
  State<TesdaView> createState() => _TesdaViewState();
}

class _TesdaViewState extends State<TesdaView> {
  int _page = 1;
  late Future<Map<String, dynamic>> _catalog;

  @override
  void initState() {
    super.initState();
    _catalog = ApiClient.instance.tesdaCatalog();
  }

  void _loadPage(int page) {
    setState(() {
      _page = page;
      _catalog = ApiClient.instance.tesdaCatalog(page: page);
    });
  }

  Future<void> _openResource(String rawUrl) async {
    final uri = Uri.tryParse(rawUrl);
    if (uri == null || !uri.hasScheme) {
      throw const FormatException('The TESDA resource URL is invalid.');
    }
    final opened = await launchUrl(uri, mode: LaunchMode.externalApplication);
    if (!opened && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open the TESDA resource.')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);
    return FutureBuilder<Map<String, dynamic>>(
      future: _catalog,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(
            child: CircularProgressIndicator(color: TkColors.violet),
          );
        }
        if (snapshot.hasError) {
          final message = snapshot.error is ApiException
              ? (snapshot.error as ApiException).message
              : 'Could not load the TESDA catalog. Check your connection.';
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: TkCard(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(message, textAlign: TextAlign.center),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: () => _loadPage(_page),
                      child: const Text('Try again'),
                    ),
                  ],
                ),
              ),
            ),
          );
        }

        final data = snapshot.data!;
        final programs = asList(data['programs']);
        final resources = asList(data['resources']);
        final pagination = asMap(data['pagination']);
        final currentPage = (pagination['current_page'] as num?)?.toInt() ?? 1;
        final lastPage = (pagination['last_page'] as num?)?.toInt() ?? 1;
        final total = (pagination['total'] as num?)?.toInt() ?? 0;

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
                      'TESDA training and services',
                      style: tkText(
                        context,
                        size: 27,
                        weight: 500,
                        spacing: -0.7,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Explore TESDA Lingayen programs and official training, scholarship, and certification resources for Pangasinan.',
                      style: tkText(
                        context,
                        size: 14,
                        color: colors.muted,
                        height: 1.45,
                      ),
                    ),
                    const SizedBox(height: 18),
                    TkCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Find registered programs by NC level',
                            style: tkText(context, size: 17, weight: 500),
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Search TESDA’s live provider directory for National Certificates I to IV in Pangasinan. Confirm schedules, slots, fees, and eligibility with the provider.',
                            style: tkText(
                              context,
                              size: 13,
                              color: colors.muted,
                              height: 1.4,
                            ),
                          ),
                          const SizedBox(height: 14),
                          Wrap(
                            spacing: 8,
                            runSpacing: 8,
                            children: [
                              for (final resource in resources)
                                if ('${asMap(resource)['title']}'.startsWith('NC '))
                                  OutlinedButton(
                                    onPressed: () => _openResource(
                                      '${asMap(resource)['url']}',
                                    ),
                                    child: Text(
                                      '${asMap(resource)['title']}',
                                    ),
                                  ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    TkCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'TESDA Lingayen programs',
                                  style: tkText(
                                    context,
                                    size: 17,
                                    weight: 500,
                                  ),
                                ),
                              ),
                              TkChip(label: '$total published'),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'These programs are published in Tuklas by authorized local staff. Confirm current schedules, slots, requirements, and scholarships with TESDA or the provider.',
                            style: tkText(
                              context,
                              size: 12.5,
                              color: colors.muted,
                              height: 1.4,
                            ),
                          ),
                          const SizedBox(height: 12),
                          if (programs.isEmpty)
                            Text(
                              'No local programs have been published yet. Use the NC I–IV searches above to find current provider listings.',
                              style: tkText(
                                context,
                                size: 13.5,
                                color: colors.muted,
                              ),
                            )
                          else
                            for (final raw in programs)
                              _programCard(context, asMap(raw)),
                          if (lastPage > 1) ...[
                            const SizedBox(height: 12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                OutlinedButton(
                                  onPressed: currentPage > 1
                                      ? () => _loadPage(currentPage - 1)
                                      : null,
                                  child: const Text('Previous'),
                                ),
                                Text(
                                  'Page $currentPage of $lastPage',
                                  style: tkText(
                                    context,
                                    size: 12.5,
                                    color: colors.muted,
                                  ),
                                ),
                                OutlinedButton(
                                  onPressed: currentPage < lastPage
                                      ? () => _loadPage(currentPage + 1)
                                      : null,
                                  child: const Text('Next'),
                                ),
                              ],
                            ),
                          ],
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    TkCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'More ways to learn and get certified',
                            style: tkText(context, size: 17, weight: 500),
                          ),
                          const SizedBox(height: 8),
                          for (final resource in resources)
                            if (!'${asMap(resource)['title']}'.startsWith('NC '))
                              ListTile(
                                contentPadding: EdgeInsets.zero,
                                title: Text(
                                  '${asMap(resource)['title']}',
                                  style: tkText(context, size: 14, weight: 500),
                                ),
                                trailing: const Icon(
                                  Icons.open_in_new,
                                  size: 18,
                                ),
                                onTap: () => _openResource(
                                  '${asMap(resource)['url']}',
                                ),
                              ),
                          const SizedBox(height: 4),
                          Text(
                            '${data['notice'] ?? ''}',
                            style: tkText(
                              context,
                              size: 12,
                              color: colors.muted,
                              height: 1.4,
                            ),
                          ),
                        ],
                      ),
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

  Widget _programCard(BuildContext context, Map<String, dynamic> program) {
    final colors = TkColors.of(context);
    final duration = program['duration_hours'];
    final verified = program['last_verified_at'];

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: colors.surface2,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  '${program['nc_level'] ?? 'TESDA training program'}',
                  style: tkText(
                    context,
                    size: 12.5,
                    color: colors.muted,
                    weight: 500,
                  ),
                ),
              ),
              if (verified != null)
                Text(
                  'Checked $verified',
                  style: tkText(context, size: 11.5, color: colors.muted),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            '${program['title'] ?? ''}',
            style: tkText(context, size: 15, weight: 500),
          ),
          if (program['description'] != null) ...[
            const SizedBox(height: 6),
            Text(
              '${program['description']}',
              style: tkText(
                context,
                size: 13,
                color: colors.ink2,
                height: 1.4,
              ),
            ),
          ],
          if (duration != null || program['schedule_note'] != null) ...[
            const SizedBox(height: 8),
            if (duration != null)
              Text(
                '$duration training hours',
                style: tkText(context, size: 12, color: colors.muted),
              ),
            if (program['schedule_note'] != null)
              Text(
                '${program['schedule_note']}',
                style: tkText(context, size: 12, color: colors.muted),
              ),
          ],
        ],
      ),
    );
  }
}
