import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
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
  final _search = TextEditingController();
  String _query = '';

  bool get _searchable =>
      ['Jobs', 'Scholarships', 'Centers', 'Sessions'].contains(widget.section);

  String get _role => '${ApiClient.instance.user?['role']}';
  String get _path => switch (widget.section) {
    'Jobs' => '/opportunities',
    'Scholarships' => '/scholarships',
    'Centers' => '/training-centers',
    'Trainers' => '/center-trainers',
    'Sessions' => '/training-sessions',
    'Applications' => '/training-applications',
    _ => '/assessment-records',
  };
  String get _key => switch (widget.section) {
    'Jobs' => 'jobs',
    'Scholarships' => 'scholarships',
    'Centers' => 'centers',
    'Trainers' => 'assignments',
    'Sessions' => 'sessions',
    'Applications' => 'applications',
    _ => 'records',
  };
  bool get _canManage => switch (widget.section) {
    'Jobs' => _role == 'super_admin',
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
  String get _helpText => switch (widget.section) {
    'Jobs' => 'Browse openings. PESO staff enter the employer and listing together, verify current details, then publish and recommend the opening.',
    'Scholarships' => 'Find support for training. Check the provider source, eligibility, required documents, assessment, and deadline before applying.',
    'Centers' => 'See the training centers that provide Tuklas programs.',
    'Trainers' => 'Assign a trainer to a published program at the same center.',
    'Sessions' => 'See upcoming training dates and available places. Open a session to request a place.',
    'Applications' =>
      'Track a training request. Staff can accept or decline pending requests.',
    _ => 'See recorded assessment dates and results for your training.',
  };

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _applySearch() {
    setState(() {
      _query = _search.text.trim();
      _page = 1;
    });
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _busy = true;
      _error = null;
      _response = null;
    });
    try {
      final query = _query.isEmpty
          ? ''
          : '&q=${Uri.encodeQueryComponent(_query)}';
      final response = await ApiClient.instance.workspace(
        '$_path?page=$_page$query',
      );
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
    'Jobs' => [
      const WorkspaceField(
        'employer_name',
        'Employer or organization',
        required: true,
      ),
      const WorkspaceField(
        'employer_verified',
        'I checked that the employer and opening are current',
        options: ['true', 'false'],
        optionLabels: {'true': 'Yes', 'false': 'No'},
        required: true,
      ),
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
      WorkspaceField(
        'requirements',
        'Application requirements',
        multiline: true,
      ),
      WorkspaceField(
        'assessment_type',
        'Assessment type',
        options: [
          '',
          'None',
          'Exam',
          'Interview',
          'Skills assessment',
          'Other',
        ],
        optionLabels: {'': 'Not specified by provider'},
      ),
      WorkspaceField(
        'assessment_details',
        'Assessment instructions',
        multiline: true,
      ),
      WorkspaceField(
        'source_url',
        'Official information source (required to publish)',
      ),
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

  bool _hasScholarshipApplication(int scholarshipId) => _list('myApplications')
      .any(
        (application) =>
            (application['scholarship'] as Map?)?['id'] == scholarshipId,
      );

  Future<void> _applyForScholarship(Map<String, dynamic> scholarship) async {
    final messageController = TextEditingController();
    var documents = <PlatformFile>[];
    final submission = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (dialogContext) => StatefulBuilder(
        builder: (dialogContext, setDialogState) => AlertDialog(
          title: const Text('Apply for scholarship'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${scholarship['title']} · ${scholarship['provider']}'),
                const SizedBox(height: 12),
                TextField(
                  controller: messageController,
                  minLines: 3,
                  maxLines: 6,
                  maxLength: 3000,
                  onChanged: (_) => setDialogState(() {}),
                  decoration: const InputDecoration(
                    labelText: 'Why are you applying?',
                    helperText: 'Write at least 20 characters.',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: () async {
                    final selected = await FilePicker.pickFiles(
                      type: FileType.custom,
                      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
                      allowMultiple: true,
                      withData: true,
                    );
                    if (selected == null || !dialogContext.mounted) return;
                    if (selected.files.length > 5) {
                      ScaffoldMessenger.of(dialogContext).showSnackBar(
                        const SnackBar(
                          content: Text('Choose up to five documents.'),
                        ),
                      );
                      return;
                    }
                    setDialogState(() => documents = selected.files);
                  },
                  icon: const Icon(Icons.attach_file),
                  label: const Text('Attach requirements'),
                ),
                for (final document in documents)
                  Text(
                    '${document.name} · ${(document.size / 1024).ceil()} KB',
                  ),
                const Text(
                  'Attach at least one PDF, JPG, or PNG. Up to 10 MB per file.',
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed:
                  messageController.text.trim().length < 20 || documents.isEmpty
                  ? null
                  : () {
                      Navigator.pop(dialogContext, {
                        'message': messageController.text.trim(),
                        'documents': documents,
                      });
                    },
              child: const Text('Submit application'),
            ),
          ],
        ),
      ),
    );
    messageController.dispose();
    if (submission == null) return;

    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ApiClient.instance.submitScholarshipApplication(
        scholarshipId: scholarship['id'] as int,
        message: submission['message'] as String,
        documents: submission['documents'] as List<PlatformFile>,
      );
      await _load();
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = error is ApiException
              ? error.message
              : 'Could not submit the application. Please try again.',
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
      '${item['employer_name'] ?? (item['employer'] as Map?)?['name'] ?? ''} · ${item['location'] ?? ''}\n${item['description'] ?? ''}',
    'Scholarships' =>
      '${item['provider'] ?? ''}\n${item['description'] ?? ''}\nWho can apply: ${item['eligibility'] ?? 'Not specified by provider'}\nRequirements: ${item['requirements'] ?? 'Not specified by provider'}\nAssessment: ${item['assessment_type'] ?? 'Not specified by provider'}\n${item['assessment_details'] ?? ''}\nSource: ${item['source_url'] ?? 'Not specified by provider'}',
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

  String _programSummary(Map<String, dynamic> program) {
    final duration = program['duration_days'];
    return '${program['title'] ?? 'Program'} · ${duration == null ? 'Duration not specified' : '$duration training days'}';
  }

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
            Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Text(
                _helpText,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ),
            if (widget.section == 'Scholarships' && _role == 'youth') ...[
              Text(
                'My applications',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              if (_list('myApplications').isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: Text('You have not applied to a scholarship yet.'),
                ),
              for (final application in _list('myApplications'))
                Card(
                  child: ListTile(
                    title: Text(
                      '${(application['scholarship'] as Map?)?['title'] ?? 'Scholarship'}',
                    ),
                    subtitle: Text(
                      '${application['status']}\n${application['review_notes'] ?? application['application_message'] ?? ''}',
                    ),
                    isThreeLine: true,
                  ),
                ),
            ],
            if (widget.section == 'Scholarships' && _canManage) ...[
              Text(
                'Applications received',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              if (_list('applications').isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8),
                  child: Text(
                    'No scholarship applications have been submitted yet.',
                  ),
                ),
              for (final application in _list('applications'))
                Card(
                  child: ListTile(
                    title: Text(
                      '${(application['scholarship'] as Map?)?['title'] ?? 'Scholarship'}',
                    ),
                    subtitle: Text(
                      '${(application['user'] as Map?)?['name'] ?? 'Applicant'} · ${application['status']}\n${application['application_message'] ?? ''}',
                    ),
                    isThreeLine: true,
                    trailing: IconButton(
                      tooltip: 'Review application',
                      onPressed: _busy
                          ? null
                          : () async {
                              final saved = await openWorkspaceForm(
                                context,
                                title: 'Review scholarship application',
                                path:
                                    '/scholarship-applications/${application['id']}',
                                method: 'PUT',
                                fields: const [
                                  WorkspaceField(
                                    'status',
                                    'Decision',
                                    options: [
                                      'reviewing',
                                      'approved',
                                      'rejected',
                                    ],
                                    required: true,
                                  ),
                                  WorkspaceField(
                                    'review_notes',
                                    'Notes for applicant',
                                    multiline: true,
                                  ),
                                ],
                                initial: application,
                              );
                              if (saved != null) _load();
                            },
                      icon: const Icon(Icons.rate_review_outlined),
                    ),
                  ),
                ),
            ],
            if (widget.section == 'Jobs')
              Align(
                alignment: Alignment.centerLeft,
                child: TextButton.icon(
                  onPressed: () => launchUrl(
                    Uri.parse('https://philjobnet.gov.ph/job-vacancies/'),
                    mode: LaunchMode.externalApplication,
                  ),
                  icon: const Icon(Icons.open_in_new),
                  label: const Text('Browse live DOLE PhilJobNet vacancies'),
                ),
              ),
            if (_searchable)
              Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: TextField(
                  controller: _search,
                  maxLength: 100,
                  textInputAction: TextInputAction.search,
                  onSubmitted: (_) => _applySearch(),
                  decoration: InputDecoration(
                    hintText: 'Search ${widget.section.toLowerCase()}',
                    counterText: '',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (_query.isNotEmpty)
                          IconButton(
                            icon: const Icon(Icons.close),
                            tooltip: 'Clear search',
                            onPressed: () {
                              _search.clear();
                              _applySearch();
                            },
                          ),
                        IconButton(
                          icon: const Icon(Icons.arrow_forward),
                          tooltip: 'Search',
                          onPressed: _applySearch,
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            if (_busy) ...[
              const LinearProgressIndicator(),
              const Padding(
                padding: EdgeInsets.all(16),
                child: Text('Loading current results…'),
              ),
            ],
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
            if (_searchable && _response?[_key] is Map)
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text('${_response![_key]['total']} results'),
              ),
            if (_response != null && items.isEmpty)
              Padding(
                padding: const EdgeInsets.all(24),
                child: Text(
                  _query.isNotEmpty
                      ? 'No results match your search.'
                      : _canManage
                      ? 'Nothing here yet. Tap Add to create the first item.'
                      : 'Nothing here yet. Pull down to refresh later.',
                ),
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
                      if (item['is_demo'] == true) ...[
                        const SizedBox(height: 6),
                        const Text('SIMULATED DEMO DATA'),
                      ],
                      const SizedBox(height: 8),
                      Text(_details(item)),
                      if (widget.section == 'Centers' &&
                          item['programs'] is List) ...[
                        const SizedBox(height: 10),
                        Text(
                          'Program offerings (${(item['programs'] as List).length})',
                        ),
                        for (final rawProgram
                            in (item['programs'] as List).whereType<Map>())
                          Padding(
                            padding: const EdgeInsets.only(top: 5),
                            child: Text(
                              '${_programSummary(Map<String, dynamic>.from(rawProgram))}${rawProgram['is_demo'] == true ? ' · Simulated demo' : ''}',
                            ),
                          ),
                      ],
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
                      if (widget.section == 'Scholarships' &&
                          _role == 'youth' &&
                          item['id'] is int &&
                          !_hasScholarshipApplication(item['id'] as int))
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _applyForScholarship(item),
                          icon: const Icon(Icons.upload_file_outlined),
                          label: const Text('Apply and send documents'),
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
