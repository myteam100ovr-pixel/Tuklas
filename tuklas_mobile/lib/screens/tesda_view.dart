import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../date_format.dart';
import '../theme.dart';
import '../ui.dart';

class TesdaView extends StatefulWidget {
  const TesdaView({super.key, this.initialQuery});

  final String? initialQuery;

  @override
  State<TesdaView> createState() => _TesdaViewState();
}

class _TesdaViewState extends State<TesdaView> {
  int _page = 1;
  int _registeredPage = 1;
  String? _centerType;
  late String _query;
  final Set<int> _applyingSessionIds = {};
  final Set<int> _applyingProgramIds = {};
  late Future<Map<String, dynamic>> _catalog;

  bool get _isCareerSeeker => ApiClient.instance.user?['role'] == 'youth';

  @override
  void initState() {
    super.initState();
    _query = widget.initialQuery?.trim() ?? '';
    _catalog = ApiClient.instance.tesdaCatalog(
      careerSeeker: _isCareerSeeker,
      centerType: _centerType,
      query: _query,
    );
  }

  void _loadPage(int page, {int? registeredPage}) {
    setState(() {
      _page = page;
      _registeredPage = registeredPage ?? _registeredPage;
      _catalog = ApiClient.instance.tesdaCatalog(
        page: page,
        registeredPage: _registeredPage,
        careerSeeker: _isCareerSeeker,
        centerType: _centerType,
        query: _query,
      );
    });
  }

  void _filterByCenterType(String? centerType) {
    setState(() {
      _centerType = centerType;
      _page = 1;
      _registeredPage = 1;
      _catalog = ApiClient.instance.tesdaCatalog(
        careerSeeker: _isCareerSeeker,
        centerType: centerType,
        query: _query,
      );
    });
  }

  Future<void> _requestTrainingPlace(int sessionId) async {
    if (_applyingSessionIds.contains(sessionId)) return;
    setState(() => _applyingSessionIds.add(sessionId));
    try {
      final result = await ApiClient.instance.workspace(
        '/training-sessions/$sessionId/apply',
        method: 'POST',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${result['message'] ?? 'Request submitted.'}')),
      );
      _loadPage(_page);
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _applyingSessionIds.remove(sessionId));
    }
  }

  Future<void> _requestCourseReview(int programId) async {
    if (_applyingProgramIds.contains(programId)) return;
    setState(() => _applyingProgramIds.add(programId));
    try {
      final result = await ApiClient.instance.workspace(
        '/training-programs/$programId/applications',
        method: 'POST',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${result['message'] ?? 'Course request submitted.'}'),
        ),
      );
      _loadPage(_page);
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error.message)));
    } finally {
      if (mounted) setState(() => _applyingProgramIds.remove(programId));
    }
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
        final trainingRecommendations = asList(
          data['training_recommendations'],
        );
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
            trainingRecommendations: trainingRecommendations,
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
                            TkPagination(
                              currentPage: registeredCurrentPage,
                              lastPage: registeredLastPage,
                              outlined: true,
                              onPrevious: registeredCurrentPage > 1
                                  ? () => _loadPage(
                                      _page,
                                      registeredPage: registeredCurrentPage - 1,
                                    )
                                  : null,
                              onNext: registeredCurrentPage < registeredLastPage
                                  ? () => _loadPage(
                                      _page,
                                      registeredPage: registeredCurrentPage + 1,
                                    )
                                  : null,
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
                            TkPagination(
                              currentPage: currentPage,
                              lastPage: lastPage,
                              outlined: true,
                              onPrevious: currentPage > 1
                                  ? () => _loadPage(currentPage - 1)
                                  : null,
                              onNext: currentPage < lastPage
                                  ? () => _loadPage(currentPage + 1)
                                  : null,
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
    required List<dynamic> trainingRecommendations,
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
      final school = '${program['training_center'] ?? 'Training center'}';
      final type = '${program['training_center_type'] ?? ''}';
      schoolGroups.putIfAbsent('$type|$school', () => []).add(program);
    }
    final orderedSchools = schoolGroups.entries.toList()
      ..sort((left, right) {
        int centerRank(String type) => switch (type) {
          'PTC' => 0,
          'PSAT' => 1,
          'PRIVATE' => 2,
          _ => 3,
        };

        final rank = centerRank(
          '${left.value.first['training_center_type']}',
        ).compareTo(centerRank('${right.value.first['training_center_type']}'));
        return rank != 0 ? rank : left.key.compareTo(right.key);
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
                  title: 'Training courses',
                ),
                const SizedBox(height: 8),
                Text(
                  'Filter by PTC, PSAT, or private training center. Review published courses and upcoming schedules before requesting a place.',
                  style: tkText(
                    context,
                    size: 14,
                    color: colors.muted,
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 14),
                _trainingRecommendationsCard(context, trainingRecommendations),
                const SizedBox(height: 16),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    _centerTypeChip(context, 'All', null),
                    _centerTypeChip(context, 'PTC', 'PTC'),
                    _centerTypeChip(context, 'PSAT', 'PSAT'),
                    _centerTypeChip(context, 'Private', 'PRIVATE'),
                  ],
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        'Courses by training center',
                        style: tkText(context, size: 17, weight: 500),
                      ),
                    ),
                    TkChip(label: '$total listings'),
                  ],
                ),
                if (programs.isEmpty)
                  TkCard(
                    child: Text(
                      'No published courses are available for this filter yet. Try another center type or contact a listed provider to confirm current courses and schedules.',
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
                                      school.key.split('|').last,
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
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  TkChip(
                                    label: '${school.value.length} courses',
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    switch ('${school.value.first['training_center_type']}') {
                                      'PTC' => 'PTC',
                                      'PSAT' => 'PSAT',
                                      'PRIVATE' => 'Private',
                                      _ => 'Center',
                                    },
                                    style: tkText(
                                      context,
                                      size: 10.5,
                                      color: colors.muted,
                                    ),
                                  ),
                                ],
                              ),
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
                if (_centerType == null || _centerType == 'PRIVATE') ...[
                  const SizedBox(height: 16),
                  Text(
                    'Private TESDA-registered providers',
                    style: tkText(context, size: 17, weight: 500),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    'Use this directory to contact private providers when a public center has no suitable intake. Registry records do not confirm live schedules, duration, or available seats.',
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
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _centerTypeChip(BuildContext context, String label, String? value) =>
      ChoiceChip(
        label: Text(label),
        selected: _centerType == value,
        onSelected: (_) => _filterByCenterType(value),
      );

  Widget _trainingRecommendationsCard(
    BuildContext context,
    List<dynamic> recommendations,
  ) {
    final colors = TkColors.of(context);
    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Recommended from your skills assessment',
            style: tkText(context, size: 15, weight: 600),
          ),
          const SizedBox(height: 4),
          Text(
            recommendations.isEmpty
                ? 'Complete or save your skills and interests assessment to get course matches here.'
                : 'These published courses match your saved skills and interests.',
            style: tkText(context, size: 12, color: colors.muted, height: 1.4),
          ),
          if (recommendations.isNotEmpty) ...[
            const SizedBox(height: 10),
            for (final rawRecommendation in recommendations)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: colors.surface2,
                    border: Border.all(color: colors.line),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${asMap(rawRecommendation)['title'] ?? ''}',
                        style: tkText(context, size: 13, weight: 600),
                      ),
                      if ('${asMap(rawRecommendation)['detail'] ?? ''}'
                          .isNotEmpty) ...[
                        const SizedBox(height: 3),
                        Text(
                          '${asMap(rawRecommendation)['detail']}',
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
        ],
      ),
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
    final sessions = asList(program['sessions']).map(asMap).toList();
    final applicationStatus = '${program['application_status'] ?? ''}';
    final programId = int.tryParse('${program['id']}');
    final isApplying =
        programId != null && _applyingProgramIds.contains(programId);

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
          const SizedBox(height: 8),
          if (applicationStatus == 'pending' || applicationStatus == 'accepted')
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                applicationStatus == 'accepted' ? 'Course request approved' : 'Course request pending · The center will confirm an intake and available places.',
                style: tkText(
                  context,
                  size: 12,
                  weight: 600,
                  color: TkColors.violet,
                  height: 1.4,
                ),
              ),
            )
          else if (sessions.isEmpty) ...[
            Text(
              'No upcoming intake is listed yet. Send a course request and the center can confirm dates and available places.',
              style: tkText(
                context,
                size: 12,
                color: colors.muted,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 8),
            OutlinedButton.icon(
              onPressed: programId == null || isApplying
                  ? null
                  : () => _requestCourseReview(programId),
              icon: isApplying
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.send_outlined, size: 16),
              label: Text(
                isApplying ? 'Sending request…' : 'Apply for this course',
              ),
            ),
          ] else
            for (final session in sessions) ...[
              const SizedBox(height: 6),
              _careerSeekerSessionOption(
                context,
                session,
                programApplicationStatus: applicationStatus,
              ),
            ],
        ],
      ),
    );
  }

  Widget _careerSeekerSessionOption(
    BuildContext context,
    Map<String, dynamic> session, {
    String programApplicationStatus = '',
  }) {
    final colors = TkColors.of(context);
    final sessionId = int.tryParse('${session['id']}');
    final capacity = int.tryParse('${session['capacity']}') ?? 0;
    final accepted = int.tryParse('${session['accepted_count']}') ?? 0;
    final placesLeft = (capacity - accepted).clamp(0, capacity);
    final applicationStatus = programApplicationStatus.isNotEmpty
        ? programApplicationStatus
        : '${session['application_status'] ?? ''}';
    final isAlreadyApplied =
        applicationStatus == 'pending' || applicationStatus == 'accepted';
    final isApplying =
        sessionId != null && _applyingSessionIds.contains(sessionId);

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: colors.surface2,
        border: Border.all(color: colors.line),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${formatTuklasDate(session['starts_at'], includeTime: true)} · $placesLeft of $capacity places available',
            style: tkText(
              context,
              size: 11.5,
              color: colors.muted,
              height: 1.4,
            ),
          ),
          const SizedBox(height: 6),
          if (isAlreadyApplied)
            Text(
              'Request ${applicationStatus[0].toUpperCase()}${applicationStatus.substring(1)}',
              style: tkText(
                context,
                size: 12,
                weight: 600,
                color: TkColors.violet,
              ),
            )
          else if (placesLeft == 0)
            Text(
              'Fully occupied · Check the other providers below.',
              style: tkText(
                context,
                size: 12,
                weight: 600,
                color: colors.muted,
              ),
            )
          else
            OutlinedButton.icon(
              onPressed: sessionId == null || isApplying
                  ? null
                  : () => _requestTrainingPlace(sessionId),
              icon: isApplying
                  ? const SizedBox(
                      width: 14,
                      height: 14,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : const Icon(Icons.send_outlined, size: 16),
              label: Text(isApplying ? 'Sending request…' : 'Request course'),
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
