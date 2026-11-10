import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import 'workspace_form.dart';

class OperationsView extends StatefulWidget {
  const OperationsView({super.key, required this.section});

  final String section;

  @override
  State<OperationsView> createState() => _OperationsViewState();
}

class _OperationsViewState extends State<OperationsView> {
  Map<String, dynamic>? _response;
  String? _error;
  bool _busy = false;
  int _page = 1;

  String get _role => '${ApiClient.instance.user?['role']}';
  String get _path => switch (widget.section) {
    'Employers' => '/employers',
    'Jobs' => '/opportunities',
    'Scholarships' => '/scholarships',
    'Centers' => '/training-centers',
    'Trainers' => '/center-trainers',
    'Sessions' => '/training-sessions',
    'Applications' => '/training-applications',
    _ => '/assessment-records',
  };
  String get _key => switch (widget.section) {
    'Employers' => 'employers',
    'Jobs' => 'jobs',
    'Scholarships' => 'scholarships',
    'Centers' => 'centers',
    'Trainers' => 'assignments',
    'Sessions' => 'sessions',
    'Applications' => 'applications',
    _ => 'records',
  };
  bool get _canManage => switch (widget.section) {
    'Employers' || 'Jobs' => _role == 'super_admin',
    'Scholarships' ||
    'Centers' => _role == 'super_admin' || _role == 'tesda_admin',
    'Sessions' || 'Assessments' =>
      _role == 'super_admin' ||
          _role == 'tesda_admin' ||
          _role == 'center_representative',
    'Trainers' =>
      _role == 'super_admin' ||
          _role == 'tesda_admin' ||
          _role == 'center_representative',
    _ => false,
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final response = await ApiClient.instance.workspace('$_path?page=$_page');
      if (mounted) setState(() => _response = response);
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Could not load this section. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  List<Map<String, dynamic>> _list(String key) {
    final raw = _response?[key];
    final entries = raw is Map ? raw['data'] : raw;
    if (entries is! List) return [];
    return entries
        .whereType<Map>()
        .map((item) => Map<String, dynamic>.from(item))
        .toList();
  }

  Future<void> _changePage(int page) async {
    if (page < 1 || _busy) return;
    setState(() => _page = page);
    await _load();
  }

  WorkspaceField _choice(
    String name,
    String label,
    String source,
    String textKey,
  ) {
    final entries = _list(source);
    final labels = {
      for (final item in entries)
        '${item['id']}': source == 'eligibleApplications'
            ? '${(item['user'] as Map?)?['name'] ?? 'Trainee'} · ${(item['program'] as Map?)?['title'] ?? 'Program'}'
            : '${item[textKey] ?? item['id']}',
    };
    return WorkspaceField(
      name,
      label,
      required: true,
      options: labels.keys.toList(),
      optionLabels: labels,
    );
  }

  List<WorkspaceField> _fields() => switch (widget.section) {
    'Employers' => const [
      WorkspaceField('name', 'Employer name', required: true),
      WorkspaceField('location', 'Location'),
      WorkspaceField('contact_email', 'Contact email'),
      WorkspaceField('website', 'Website URL'),
      WorkspaceField(
        'is_verified',
        'Verified',
        options: ['true', 'false'],
        optionLabels: {'true': 'Yes', 'false': 'No'},
        required: true,
      ),
    ],
    'Jobs' => [
      _choice('employer_id', 'Verified employer', 'employers', 'name'),
      const WorkspaceField('title', 'Job title', required: true),
      const WorkspaceField('location', 'Location'),
      const WorkspaceField(
        'description',
        'Description',
        multiline: true,
        required: true,
      ),
      const WorkspaceField('requirements', 'Requirements', multiline: true),
      const WorkspaceField('application_url', 'Application URL'),
      const WorkspaceField('closes_at', 'Closing date (YYYY-MM-DD)'),
      const WorkspaceField(
        'status',
        'Status',
        options: ['draft', 'published', 'closed'],
        required: true,
      ),
    ],
    'Scholarships' => const [
      WorkspaceField('title', 'Title', required: true),
      WorkspaceField('provider', 'Provider', required: true),
      WorkspaceField(
        'description',
        'Description',
        multiline: true,
        required: true,
      ),
      WorkspaceField('eligibility', 'Eligibility', multiline: true),
      WorkspaceField('application_url', 'Application URL'),
      WorkspaceField('deadline', 'Deadline (YYYY-MM-DD)'),
      WorkspaceField(
        'status',
        'Status',
        options: ['draft', 'published', 'closed'],
        required: true,
      ),
    ],
    'Centers' => const [
      WorkspaceField('name', 'Center name', required: true),
      WorkspaceField('type', 'Type', options: ['PTC', 'PSAT'], required: true),
      WorkspaceField('address', 'Address'),
      WorkspaceField('contact_email', 'Contact email'),
      WorkspaceField('contact_phone', 'Phone'),
      WorkspaceField(
        'is_active',
        'Active',
        options: ['true', 'false'],
        optionLabels: {'true': 'Yes', 'false': 'No'},
        required: true,
      ),
    ],
    'Sessions' => [
      _choice('training_program_id', 'Published course', 'programs', 'title'),
      _choice('training_center_id', 'Training center', 'centers', 'name'),
      const WorkspaceField(
        'starts_at',
        'Starts (YYYY-MM-DD HH:MM)',
        required: true,
      ),
      const WorkspaceField(
        'ends_at',
        'Ends (YYYY-MM-DD HH:MM)',
        required: true,
      ),
      const WorkspaceField('capacity', 'Places', required: true),
      const WorkspaceField(
        'status',
        'Status',
        options: ['open', 'closed'],
        required: true,
      ),
    ],
    'Trainers' => [
      _choice('user_id', 'Trainer', 'trainers', 'name'),
      _choice('training_program_id', 'Program', 'programs', 'title'),
    ],
    'Assessments' => [
      _choice(
        'training_application_id',
        'Accepted trainee',
        'eligibleApplications',
        'id',
      ),
      const WorkspaceField('scheduled_at', 'Date and time (YYYY-MM-DD HH:MM)'),
      const WorkspaceField(
        'status',
        'Status',
        options: ['scheduled', 'completed', 'cancelled'],
        required: true,
      ),
      const WorkspaceField(
        'result',
        'Result',
        options: ['', 'passed', 'failed', 'pending'],
        optionLabels: {
          '': 'Pending',
          'passed': 'Passed',
          'failed': 'Failed',
          'pending': 'Awaiting result',
        },
      ),
      const WorkspaceField('notes', 'Notes', multiline: true),
    ],
    _ => [],
  };

  Future<void> _edit([Map<String, dynamic> item = const {}]) async {
    final id = item['id'];
    final path = id == null ? _path : '$_path/$id';
    final saved = await openWorkspaceForm(
      context,
      title: id == null
          ? 'Add ${widget.section.toLowerCase()}'
          : 'Edit ${widget.section.toLowerCase()}',
      path: path,
      method: id == null ? 'POST' : 'PUT',
      fields: _fields(),
      initial: item,
    );
    if (saved != null) _load();
  }

  Future<void> _action(
    String path, {
    String method = 'POST',
    Map<String, dynamic>? data,
  }) async {
    setState(() => _busy = true);
    try {
      await ApiClient.instance.workspace(path, method: method, data: data);
      await _load();
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Action failed. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  String _title(Map<String, dynamic> item) => switch (widget.section) {
    'Applications' =>
      '${(item['program'] as Map?)?['title'] ?? 'Training application'}',
    'Assessments' =>
      '${((item['application'] as Map?)?['program'] as Map?)?['title'] ?? 'Assessment'}',
    'Sessions' =>
      '${(item['program'] as Map?)?['title'] ?? 'Training session'}',
    'Trainers' => '${(item['trainer'] as Map?)?['name'] ?? 'Trainer'}',
    _ => '${item['title'] ?? item['name'] ?? 'Item'}',
  };

  String _details(Map<String, dynamic> item) => switch (widget.section) {
    'Jobs' =>
      '${(item['employer'] as Map?)?['name'] ?? ''} · ${item['location'] ?? ''}\n${item['description'] ?? ''}',
    'Scholarships' => '${item['provider'] ?? ''}\n${item['description'] ?? ''}',
    'Centers' => '${item['type'] ?? ''} · ${item['address'] ?? ''}',
    'Sessions' =>
      '${(item['center'] as Map?)?['name'] ?? ''} · ${item['starts_at'] ?? ''}\n${item['accepted_count'] ?? 0} of ${item['capacity'] ?? 0} places accepted',
    'Trainers' => '${(item['program'] as Map?)?['title'] ?? ''}',
    'Applications' =>
      '${(item['user'] as Map?)?['name'] ?? ''} · ${item['status'] ?? ''}',
    'Assessments' =>
      '${((item['application'] as Map?)?['user'] as Map?)?['name'] ?? ''} · ${item['status'] ?? ''} · ${item['result'] ?? 'Pending'}',
    _ => '${item['location'] ?? item['contact_email'] ?? ''}',
  };

  @override
  Widget build(BuildContext context) {
    final items = _list(_key);
    return Scaffold(
      appBar: AppBar(title: Text(widget.section)),
      floatingActionButton: _canManage && widget.section != 'Applications'
          ? FloatingActionButton.extended(
              onPressed: _busy ? null : () => _edit(),
              icon: const Icon(Icons.add),
              label: const Text('Add'),
            )
          : null,
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            if (_busy) const LinearProgressIndicator(),
            if (_error != null)
              Padding(
                padding: const EdgeInsets.all(12),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            if (_response == null && !_busy)
              OutlinedButton(onPressed: _load, child: const Text('Try again')),
            if (_response != null && items.isEmpty)
              const Padding(
                padding: EdgeInsets.all(24),
                child: Text('Nothing here yet.'),
              ),
            for (final item in items)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        _title(item),
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      const SizedBox(height: 8),
                      Text(_details(item)),
                      if ((widget.section == 'Jobs' ||
                              widget.section == 'Scholarships') &&
                          item['application_url'] is String &&
                          '${item['application_url']}'.isNotEmpty)
                        TextButton(
                          onPressed: () async {
                            final uri = Uri.tryParse(
                              '${item['application_url']}',
                            );
                            if (uri != null &&
                                (uri.scheme == 'https' ||
                                    uri.scheme == 'http')) {
                              await launchUrl(
                                uri,
                                mode: LaunchMode.externalApplication,
                              );
                            }
                          },
                          child: const Text('Open application link'),
                        ),
                      if (item['recommended_for_me'] == true)
                        const Padding(
                          padding: EdgeInsets.only(top: 8),
                          child: Text('Recommended by PESO'),
                        ),
                      if (_canManage &&
                          widget.section != 'Assessments' &&
                          widget.section != 'Trainers')
                        TextButton(
                          onPressed: _busy ? null : () => _edit(item),
                          child: const Text('Edit'),
                        ),
                      if (widget.section == 'Trainers' && _canManage)
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () => _action(
                                  '/center-trainers/${item['id']}',
                                  method: 'DELETE',
                                ),
                          child: const Text('Remove assignment'),
                        ),
                      if (widget.section == 'Sessions' &&
                          _role == 'youth' &&
                          item['status'] == 'open')
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () => _action(
                                  '/training-sessions/${item['id']}/apply',
                                ),
                          child: const Text('Request a place'),
                        ),
                      if (widget.section == 'Applications' &&
                          _role == 'youth' &&
                          item['status'] == 'pending')
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () => _action(
                                  '/training-applications/${item['id']}',
                                  method: 'DELETE',
                                ),
                          child: const Text('Withdraw'),
                        ),
                      if (widget.section == 'Applications' &&
                          _role != 'youth' &&
                          item['status'] == 'pending')
                        Row(
                          children: [
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _action(
                                      '/training-applications/${item['id']}',
                                      method: 'PUT',
                                      data: {'status': 'accepted'},
                                    ),
                              child: const Text('Accept'),
                            ),
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _action(
                                      '/training-applications/${item['id']}',
                                      method: 'PUT',
                                      data: {'status': 'declined'},
                                    ),
                              child: const Text('Decline'),
                            ),
                          ],
                        ),
                      if (widget.section == 'Assessments' && _canManage)
                        TextButton(
                          onPressed: _busy ? null : () => _edit(item),
                          child: const Text('Update'),
                        ),
                      if (widget.section == 'Jobs' && _canManage)
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () async {
                                  final saved = await openWorkspaceForm(
                                    context,
                                    title: 'Recommend to career seeker',
                                    path:
                                        '/opportunities/${item['id']}/recommend',
                                    fields: const [
                                      WorkspaceField(
                                        'youth_email',
                                        'Career seeker email',
                                        required: true,
                                      ),
                                    ],
                                  );
                                  if (saved != null) _load();
                                },
                          child: const Text('Recommend'),
                        ),
                    ],
                  ),
                ),
              ),
            if (_response?[_key] is Map &&
                (_response![_key] as Map)['last_page'] is int &&
                (_response![_key] as Map)['last_page'] > 1)
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  TextButton(
                    onPressed: _page > 1 && !_busy
                        ? () => _changePage(_page - 1)
                        : null,
                    child: const Text('Previous'),
                  ),
                  Text('Page $_page'),
                  TextButton(
                    onPressed:
                        _page < (_response![_key] as Map)['last_page'] && !_busy
                        ? () => _changePage(_page + 1)
                        : null,
                    child: const Text('Next'),
                  ),
                ],
              ),
          ],
        ),
      ),
    );
  }
}
