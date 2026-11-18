import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../date_format.dart';
import '../theme.dart';
import '../ui.dart';

class TesdaView extends StatefulWidget {
  const TesdaView({super.key});

  @override
  State<TesdaView> createState() => _TesdaViewState();
}

class _TesdaViewState extends State<TesdaView> {
  int _page = 1;
  int _registeredPage = 1;
  late Future<Map<String, dynamic>> _catalog;

  bool get _isCareerSeeker => ApiClient.instance.user?['role'] == 'youth';

  @override
  void initState() {
    super.initState();
    _catalog = ApiClient.instance.tesdaCatalog(careerSeeker: _isCareerSeeker);
  }

  void _loadPage(int page, {int? registeredPage}) {
    setState(() {
      _page = page;
      _registeredPage = registeredPage ?? _registeredPage;
      _catalog = ApiClient.instance.tesdaCatalog(
        page: page,
        registeredPage: _registeredPage,
        careerSeeker: _isCareerSeeker,
      );
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
        final registeredPrograms = asList(data['registered_programs']);
        final registeredPagination = asMap(
          data['registered_programs_pagination'],
        );
        final registeredCurrentPage =
            (registeredPagination['current_page'] as num?)?.toInt() ?? 1;
        final registeredLastPage =
            (registeredPagination['last_page'] as num?)?.toInt() ?? 1;
        final registeredTotal =
            (registeredPagination['total'] as num?)?.toInt() ?? 0;

        if (_isCareerSeeker) {
          return _careerSeekerCatalog(
            context,
            programs: programs,
            currentPage: currentPage,
            lastPage: lastPage,
            total: total,
            registeredPrograms: registeredPrograms,
            registeredCurrentPage: registeredCurrentPage,
            registeredLastPage: registeredLastPage,
            registeredTotal: registeredTotal,
            notice: '${data['notice'] ?? ''}',
            sourceFetchedAt: '${registeredPagination['fetched_at'] ?? ''}',
          );
        }

        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 900),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const TkPageHeading(
                      eyebrow: 'LEARN WHAT COMES NEXT',
                      title: 'TESDA training and services',
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Explore TESDA programs and official training, scholarship, and certification resources for Pangasinan.',
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
                                if ('${asMap(resource)['title']}'.startsWith(
                                  'NC ',
                                ))
                                  OutlinedButton(
                                    onPressed: () => _openResource(
                                      '${asMap(resource)['url']}',
                                    ),
                                    child: Text('${asMap(resource)['title']}'),
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
                                  'Official registered centers',
                                  style: tkText(context, size: 17, weight: 500),
                                ),
                              ),
                              TkChip(label: '$registeredTotal programs'),
                            ],
                          ),
                          const SizedBox(height: 6),
                          Text(
                            'Provider locations and programs from TESDA’s Pangasinan registry.',
                            style: tkText(
                              context,
                              size: 12.5,
                              color: colors.muted,
                              height: 1.4,
                            ),
                          ),
                          const SizedBox(height: 10),
                          for (final raw in registeredPrograms)
                            _registeredProgramCard(context, asMap(raw)),
                          if (registeredLastPage > 1) ...[
                            const SizedBox(height: 12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                OutlinedButton(
                                  onPressed: registeredCurrentPage > 1
                                      ? () => _loadPage(
                                          _page,
                                          registeredPage:
                                              registeredCurrentPage - 1,
                                        )
                                      : null,
                                  child: const Text('Previous'),
                                ),
                                Text(
                                  'Page $registeredCurrentPage of $registeredLastPage',
                                  style: tkText(
                                    context,
                                    size: 12.5,
                                    color: colors.muted,
                                  ),
                                ),
                                OutlinedButton(
                                  onPressed:
                                      registeredCurrentPage < registeredLastPage
                                      ? () => _loadPage(
                                          _page,
                                          registeredPage:
                                              registeredCurrentPage + 1,
                                        )
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
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'TESDA programs',
                                  style: tkText(context, size: 17, weight: 500),
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
                            if (!'${asMap(resource)['title']}'.startsWith(
                              'NC ',
                            ))
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
                                onTap: () =>
                                    _openResource('${asMap(resource)['url']}'),
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

  Widget _careerSeekerCatalog(
    BuildContext context, {
    required List<dynamic> programs,
    required int currentPage,
    required int lastPage,
    required int total,
    required List<dynamic> registeredPrograms,
    required int registeredCurrentPage,
    required int registeredLastPage,
    required int registeredTotal,
    required String notice,
    required String sourceFetchedAt,
  }) {
    final colors = TkColors.of(context);
    final schoolGroups = <String, List<Map<String, dynamic>>>{};
    for (final rawProgram in programs) {
      final program = asMap(rawProgram);
      final school = '${program['training_center'] ?? 'PTC or PSAT'}';
      schoolGroups.putIfAbsent(school, () => []).add(program);
    }
    final orderedSchools = schoolGroups.entries.toList()
      ..sort((left, right) {
        final leftType = left.value.first['training_center_type'] == 'PTC'
            ? 0
            : 1;
        final rightType = right.value.first['training_center_type'] == 'PTC'
            ? 0
            : 1;
        return leftType.compareTo(rightType);
      });

    final providerGroups = <String, List<Map<String, dynamic>>>{};
    for (final rawProgram in registeredPrograms) {
      final program = asMap(rawProgram);
      final provider = '${program['provider'] ?? 'Training provider'}';
      final address = '${program['address'] ?? 'Address not available'}';
      providerGroups.putIfAbsent('$provider|$address', () => []).add(program);
    }
    final orderedProviders = providerGroups.entries.toList()
      ..sort((left, right) {
        int priority(String name) {
          final normalized = name.toLowerCase();
          if (normalized.contains('edson')) return 0;
          if (normalized.contains('ledesma')) return 1;
          return 2;
        }

        final priorityOrder = priority('${left.value.first['provider']}')
            .compareTo(priority('${right.value.first['provider']}'));
        return priorityOrder != 0
            ? priorityOrder
            : '${left.value.first['provider']}'.compareTo(
                '${right.value.first['provider']}',
              );
      });

    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
      children: [
        Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 900),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const TkPageHeading(
                  eyebrow: 'TRAINING OPPORTUNITIES',
                  title: 'PTC and PSAT courses',
                ),
                const SizedBox(height: 8),
                Text(
                  'See the courses and training days listed by each public school. Course listings do not confirm that enrollment is currently open.',
                  style: tkText(
                    context,
                    size: 14,
                    color: colors.muted,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Courses at PTC and PSAT',
                        style: tkText(context, size: 17, weight: 500),
                      ),
                    ),
                    TkChip(label: '$total listings'),
                  ],
                ),
                if (programs.isEmpty)
                  TkCard(
                    child: Text(
                      'No PTC or PSAT courses are currently published. Check the other registered providers below and contact them to confirm enrollment.',
                      style: tkText(context, size: 13, color: colors.muted),
                    ),
                  )
                else
                  for (final school in orderedSchools)
                    TkCard(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      school.key,
                                      style: tkText(
                                        context,
                                        size: 16,
                                        weight: 600,
                                      ),
                                    ),
                                    if ('${school.value.first['training_center_address'] ?? ''}'
                                        .trim()
                                        .isNotEmpty) ...[
                                      const SizedBox(height: 3),
                                      Text(
                                        '${school.value.first['training_center_address']}',
                                        style: tkText(
                                          context,
                                          size: 11.5,
                                          color: colors.muted,
                                          height: 1.35,
                                        ),
                                      ),
                                    ],
                                  ],
                                ),
                              ),
                              TkChip(label: '${school.value.length} courses'),
                            ],
                          ),
                          const SizedBox(height: 8),
                          for (
                            var index = 0;
                            index < school.value.length;
                            index++
                          ) ...[
                            if (index > 0)
                              Divider(height: 1, color: colors.line),
                            _careerSeekerCourseTile(
                              context,
                              school.value[index],
                            ),
                          ],
                        ],
                      ),
                    ),
                if (lastPage > 1) ...[
                  const SizedBox(height: 8),
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
                        style: tkText(context, size: 12, color: colors.muted),
                      ),
                      OutlinedButton(
                        onPressed: currentPage < lastPage
                            ? () => _loadPage(currentPage + 1)
                            : null,
                        child: const Text('More'),
                      ),
                    ],
                  ),
                ],
                const SizedBox(height: 16),
                Text(
                  'Other TESDA-registered providers',
                  style: tkText(context, size: 17, weight: 500),
                ),
                const SizedBox(height: 6),
                Text(
                  'If PTC or PSAT has no open intake for a course, check these other schools, including Edson and Ledesma. The registry does not show live schedules, duration, or available seats, so contact the provider first.',
                  style: tkText(
                    context,
                    size: 12.5,
                    color: colors.muted,
                    height: 1.4,
                  ),
                ),
                const SizedBox(height: 10),
                if (registeredPrograms.isEmpty)
                  TkCard(
                    child: Text(
                      'No other provider listings were found.',
                      style: tkText(context, size: 13, color: colors.muted),
                    ),
                  )
                else
                  for (final provider in orderedProviders)
                    _careerSeekerProviderGroup(
                      context,
                      provider.value.first,
                      provider.value,
                    ),
                if (registeredLastPage > 1) ...[
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      OutlinedButton(
                        onPressed: registeredCurrentPage > 1
                            ? () => _loadPage(
                                _page,
                                registeredPage: registeredCurrentPage - 1,
                              )
                            : null,
                        child: const Text('Previous providers'),
                      ),
                      Text(
                        'Page $registeredCurrentPage of $registeredLastPage · $registeredTotal',
                        style: tkText(context, size: 12, color: colors.muted),
                      ),
                      OutlinedButton(
                        onPressed: registeredCurrentPage < registeredLastPage
                            ? () => _loadPage(
                                _page,
                                registeredPage: registeredCurrentPage + 1,
                              )
                            : null,
                        child: const Text('More providers'),
                      ),
                    ],
                  ),
                ],
                if (sourceFetchedAt.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Text(
                    'TESDA registry snapshot: ${formatTuklasDate(sourceFetchedAt)}',
                    style: tkText(context, size: 11.5, color: colors.muted),
                  ),
                ],
                if (notice.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(
                    notice,
                    style: tkText(
                      context,
                      size: 11.5,
                      color: colors.muted,
                      height: 1.35,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _careerSeekerCourseTile(
    BuildContext context,
    Map<String, dynamic> program,
  ) {
    final colors = TkColors.of(context);
    final duration = program['duration_days'] != null
        ? '${program['duration_days']} training days'
        : program['duration_hours'] != null
        ? '${program['duration_hours']} training hours'
        : 'Training duration not listed in the school schedule';

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 9),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${program['title'] ?? ''}',
            style: tkText(context, size: 14, weight: 500),
          ),
          const SizedBox(height: 3),
          Text(
            duration,
            style: tkText(context, size: 12, color: colors.muted, height: 1.4),
          ),
        ],
      ),
    );
  }

  Widget _careerSeekerProviderGroup(
    BuildContext context,
    Map<String, dynamic> provider,
    List<Map<String, dynamic>> programs,
  ) {
    final colors = TkColors.of(context);
    final courses =
        programs
            .map(
              (program) =>
                  '${program['program'] ?? 'Registered qualification'}',
            )
            .toSet()
            .toList()
          ..sort();
    final phoneNumbers = programs
        .map((program) => '${program['phone'] ?? ''}'.trim())
        .where((phone) => phone.isNotEmpty)
        .toSet()
        .join(' · ');

    return TkCard(
      child: ExpansionTile(
        tilePadding: EdgeInsets.zero,
        childrenPadding: EdgeInsets.zero,
        title: Text(
          '${provider['provider'] ?? 'Training provider'}',
          style: tkText(context, size: 14, weight: 600),
        ),
        subtitle: Text(
          [
            '${provider['address'] ?? 'Address not available'}',
            if (phoneNumbers.isNotEmpty) phoneNumbers,
            '${courses.length} registered courses',
          ].join(' · '),
          style: tkText(context, size: 11.5, color: colors.muted, height: 1.4),
        ),
        children: [
          for (final course in courses)
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: Text(course, style: tkText(context, size: 12.5)),
              subtitle: Text(
                'Training duration and current enrollment are not listed by TESDA.',
                style: tkText(context, size: 11, color: colors.muted),
              ),
            ),
        ],
      ),
    );
  }

  Widget _programCard(BuildContext context, Map<String, dynamic> program) {
    final colors = TkColors.of(context);
    final durationDays = program['duration_days'];
    final durationHours = program['duration_hours'];
    final duration = durationDays != null
        ? '$durationDays training days'
        : durationHours != null
        ? '$durationHours training hours'
        : null;
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
                  'Checked ${formatTuklasDate(verified)}',
                  style: tkText(context, size: 11.5, color: colors.muted),
                ),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            '${program['title'] ?? ''}',
            style: tkText(context, size: 15, weight: 500),
          ),
          if (program['training_center'] != null) ...[
            const SizedBox(height: 4),
            Text(
              '${program['training_center']}',
              style: tkText(context, size: 12, color: colors.muted),
            ),
          ],
          if (program['description'] != null) ...[
            const SizedBox(height: 6),
            Text(
              '${program['description']}',
              style: tkText(context, size: 13, color: colors.ink2, height: 1.4),
            ),
          ],
          if (duration != null || program['schedule_note'] != null) ...[
            const SizedBox(height: 8),
            if (duration != null)
              Text(
                duration,
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

  Widget _registeredProgramCard(
    BuildContext context,
    Map<String, dynamic> program,
  ) {
    final colors = TkColors.of(context);

    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: colors.surface2,
        border: Border.all(color: colors.line),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${program['program'] ?? 'TESDA registered program'}',
            style: tkText(context, size: 13.5, weight: 600),
          ),
          const SizedBox(height: 5),
          Text(
            '${program['provider'] ?? 'Training provider'}',
            style: tkText(
              context,
              size: 12.5,
              weight: 600,
              color: TkColors.violet,
            ),
          ),
          Text(
            '${program['address'] ?? 'Address not available'}',
            style: tkText(
              context,
              size: 11.5,
              color: colors.muted,
              height: 1.4,
            ),
          ),
          if (program['phone'] != null &&
              '${program['phone']}'.trim().isNotEmpty)
            Text(
              '${program['phone']}',
              style: tkText(context, size: 11.5, color: colors.muted),
            ),
        ],
      ),
    );
  }
}
