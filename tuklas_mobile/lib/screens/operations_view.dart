import 'dart:async';

import 'package:flutter/material.dart';
import 'package:file_picker/file_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../date_format.dart';
import '../ui.dart';
import '../workspace_realtime.dart';
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
  Timer? _syncTimer;
  bool _syncing = false;

  bool get _searchable => [
    'Jobs',
    'Scholarships',
    'Centers',
    'Sessions',
    'Career paths',
  ].contains(widget.section);

  String get _role => '${ApiClient.instance.user?['role']}';
  String get _path => switch (widget.section) {
    'Jobs' => '/opportunities',
    'Career paths' => '/career-paths',
    'Job applications' => '/job-applications',
    'Scholarships' => '/scholarships',
    'Centers' => '/training-centers',
    'Trainers' => '/center-trainers',
    'Sessions' => '/training-sessions',
    'Applications' => '/training-applications',
    _ => '/assessment-records',
  };
  String get _key => switch (widget.section) {
    'Jobs' => 'jobs',
    'Career paths' => 'careers',
    'Job applications' => 'applications',
    'Scholarships' => 'scholarships',
    'Centers' => 'centers',
    'Trainers' => 'assignments',
    'Sessions' => 'sessions',
    'Applications' => 'applications',
    _ => 'records',
  };
  bool get _canManage => switch (widget.section) {
    'Jobs' || 'Job applications' => _role == 'admin',
    'Career paths' => _role == 'admin',
    'Scholarships' || 'Centers' => _role == 'admin' || _role == 'tesda_admin',
    'Sessions' || 'Assessments' => _role == 'admin' || _role == 'tesda_admin',
    'Trainers' => _role == 'admin' || _role == 'tesda_admin',
    _ => false,
  };
  String get _helpText => switch (widget.section) {
    'Jobs' => 'Browse openings. PESO staff enter the employer and listing together, verify current details, then publish and recommend the opening.',
    'Career paths' => 'Manage published career guidance paths. Career Seekers can browse published paths; Admin can create, edit, publish, archive, and remove them.',
    'Job applications' => 'Review Career Seeker applications, move them under review, record hiring decisions, and give a reason when someone is not selected.',
    'Scholarships' => 'PTC and PSAT courses can each have a scholarship listing. Places are first come, first served. A submitted application reserves one place while the center and a different TESDA trainer review it.',
    'Centers' => 'See the training centers that provide Tuklas programs.',
    'Trainers' =>
      'Assign TESDA staff to a published program at the same center.',
    'Sessions' => 'See upcoming training dates and available places. Open a session to request a place.',
    'Applications' => 'Review course requests. Accept or decline pending requests, then assign an intake when dates and places are available.',
    _ => 'See recorded assessment dates and results for your training.',
  };

  @override
  void initState() {
    super.initState();
    _load();
    _syncTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      if (!WorkspaceRealtime.instance.isConnected) _sync();
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
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

  Future<void> _sync() async {
    if (!mounted ||
        _busy ||
        _syncing ||
        ModalRoute.of(context)?.isCurrent != true) {
      return;
    }
    _syncing = true;
    try {
      final query = _query.isEmpty
          ? ''
          : '&q=${Uri.encodeQueryComponent(_query)}';
      final result = await ApiClient.instance.workspace(
        '$_path?page=$_page$query',
      );
      if (mounted) setState(() => _response = result);
    } catch (_) {
      // Keep the last successful view until the next sync.
    } finally {
      _syncing = false;
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

  List<Map<String, dynamic>> _recommendedUsers(Map<String, dynamic> job) {
    final users = job['recommended_users'];
    if (users is! List) return [];
    return users
        .whereType<Map>()
        .map((user) => Map<String, dynamic>.from(user))
        .toList();
  }

  Future<void> _markNotSelected(
    Map<String, dynamic> job,
    Map<String, dynamic> seeker,
  ) async {
    final reasonController = TextEditingController();
    final reason = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Reason for non-selection'),
        content: TextField(
          controller: reasonController,
          autofocus: true,
          minLines: 3,
          maxLines: 6,
          maxLength: 3000,
          decoration: const InputDecoration(
            labelText: 'Feedback for the career seeker',
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () =>
                Navigator.pop(dialogContext, reasonController.text.trim()),
            child: const Text('Send decision'),
          ),
        ],
      ),
    );
    reasonController.dispose();
    if (reason == null || reason.length < 5 || !mounted) return;
    await _action(
      '/opportunities/${job['id']}/not-selected',
      data: {'youth_email': seeker['email'], 'reason': reason},
    );
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
            : source == 'programs' && item['center'] is Map
            ? '${(item['center'] as Map)['type'] ?? 'Center'} · ${(item['center'] as Map)['name'] ?? 'Training center'} — ${item[textKey] ?? item['id']}'
            : source == 'centers' && item['type'] != null
            ? '${item['type']} · ${item[textKey] ?? item['id']}'
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
    'Career paths' => [
      const WorkspaceField('title', 'Career path title', required: true),
      const WorkspaceField('description', 'Description', multiline: true),
      const WorkspaceField('education_level', 'Education or entry level'),
      const WorkspaceField('source_note', 'Source or reference'),
      const WorkspaceField(
        'status',
        'Status',
        options: ['draft', 'published', 'archived'],
        required: true,
      ),
    ],
    'Scholarships' => [
      WorkspaceField('title', 'Title', required: true),
      WorkspaceField('provider', 'Provider', required: true),
      _centerChoice(),
      _courseChoice(),
      const WorkspaceField(
        'slot_limit',
        'Available scholarship slots',
        required: true,
      ),
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
        'Official information source (required for TESDA approval)',
      ),
      WorkspaceField('application_url', 'Application URL'),
      WorkspaceField('deadline', 'Deadline (YYYY-MM-DD)'),
      WorkspaceField(
        'status',
        'Next step',
        options: ['draft', 'review', 'published', 'closed'],
        optionLabels: {
          'draft': 'Save draft',
          'review': 'Submit for TESDA center and trainer approval',
          'published': 'Request approval before public listing',
          'closed': 'Closed',
        },
        required: true,
      ),
    ],
    'Centers' => const [
      WorkspaceField('name', 'Center name', required: true),
      WorkspaceField(
        'type',
        'Type',
        options: ['PTC', 'PSAT', 'PRIVATE'],
        optionLabels: {
          'PTC': 'PTC · Provincial Training Center',
          'PSAT': 'PSAT · Pangasinan School of Arts and Trades',
          'PRIVATE': 'Private training center',
        },
        required: true,
      ),
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
      _choice('user_id', 'TESDA staff', 'trainers', 'name'),
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

  WorkspaceField _centerChoice() {
    final centers = _list('centers');
    final labels = <String, String>{
      '': 'Select a TESDA center',
      for (final center in centers)
        '${center['id']}': '${center['type'] ?? 'TESDA'} · ${center['name']}',
    };

    return WorkspaceField(
      'training_center_id',
      'TESDA training center',
      options: labels.keys.toList(),
      optionLabels: labels,
    );
  }

  WorkspaceField _courseChoice() {
    final programs = _list('programs');
    final labels = <String, String>{
      '': 'Select a PTC or PSAT course',
      for (final program in programs)
        '${program['id']}':
            '${(program['center'] as Map?)?['type'] ?? 'TESDA'} · ${(program['center'] as Map?)?['name'] ?? 'Training center'} — ${program['title']}',
    };

    return WorkspaceField(
      'training_program_id',
      'Course receiving the scholarship',
      options: labels.keys.toList(),
      optionLabels: labels,
      required: true,
    );
  }

  Future<void> _edit([Map<String, dynamic> item = const {}]) async {
    if (widget.section == 'Trainers' && item.isEmpty) {
      final pairs = _list('assignment_pairs');
      final labels = <String, String>{
        for (final pair in pairs)
          '${pair['user_id']}:${pair['training_program_id']}':
              '${pair['trainer_name']} · ${pair['center_name']} · ${pair['program_title']}',
      };
      if (labels.isEmpty) {
        setState(
          () => _error = 'No valid staff and published program combinations are available.',
        );
        return;
      }
      final saved = await openWorkspaceForm(
        context,
        title: 'Assign TESDA staff',
        path: _path,
        fields: [
          WorkspaceField(
            'assignment_pair',
            'Staff, center, and program',
            options: labels.keys.toList(),
            optionLabels: labels,
            required: true,
          ),
        ],
      );
      if (saved != null) await _load();
      return;
    }
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

  bool _hasAvailableTrainingIntake(Map<String, dynamic> application) {
    final program = application['program'] as Map?;
    final sessions = program?['sessions'];
    if (sessions is! List) return false;

    return sessions.whereType<Map>().any((session) {
      final capacity = int.tryParse('${session['capacity']}') ?? 0;
      final accepted = int.tryParse('${session['accepted_count']}') ?? 0;

      return session['id'] != null && capacity > accepted;
    });
  }

  Future<void> _acceptTrainingApplication(
    Map<String, dynamic> application,
  ) async {
    final applicationId = application['id'];
    final isAccepted = application['status'] == 'accepted';
    final existingSession = application['session'] as Map?;
    final program = application['program'] as Map?;
    final sessions = (program?['sessions'] as List? ?? const [])
        .whereType<Map>()
        .map((session) => Map<String, dynamic>.from(session))
        .where((session) {
          final capacity = int.tryParse('${session['capacity']}') ?? 0;
          final accepted = int.tryParse('${session['accepted_count']}') ?? 0;
          return capacity > accepted && session['id'] != null;
        })
        .toList();
    final existingSessionIsAvailable =
        existingSession != null &&
        sessions.any(
          (session) => '${session['id']}' == '${existingSession['id']}',
        );
    if (isAccepted && existingSession != null) return;
    if (!isAccepted && existingSessionIsAvailable) {
      await _action(
        '/training-applications/$applicationId',
        method: 'PUT',
        data: {
          'status': 'accepted',
          'training_session_id': existingSession['id'],
        },
      );
      return;
    }

    if (sessions.isEmpty) {
      if (!isAccepted) {
        final data = <String, dynamic>{'status': 'accepted'};
        if (existingSession != null) data['training_session_id'] = null;
        await _action(
          '/training-applications/$applicationId',
          method: 'PUT',
          data: data,
        );
      } else {
        setState(() {
          _error = 'There are no open intakes with places available. Add an open training date and center before assigning this trainee.';
        });
      }
      return;
    }

    final sessionOptions = <String>[];
    final sessionLabels = <String, String>{};
    for (final session in sessions) {
      final id = '${session['id']}';
      final center = (session['center'] as Map?)?['name'] ?? 'Training center';
      final date = formatTuklasDate(session['starts_at'], includeTime: true);
      final capacity = int.tryParse('${session['capacity']}') ?? 0;
      final accepted = int.tryParse('${session['accepted_count']}') ?? 0;
      sessionOptions.add(id);
      sessionLabels[id] =
          '$center · $date · ${capacity - accepted} places left';
    }

    final saved = await openWorkspaceForm(
      context,
      title: isAccepted ? 'Assign training intake' : 'Accept training request',
      path: '/training-applications/$applicationId',
      method: 'PUT',
      initial: const {'status': 'accepted'},
      fields: [
        WorkspaceField(
          'status',
          'Decision',
          options: const ['accepted'],
          optionLabels: const {'accepted': 'Accept'},
          required: true,
        ),
        WorkspaceField(
          'training_session_id',
          'Intake and training center',
          options: isAccepted ? sessionOptions : ['', ...sessionOptions],
          optionLabels: {
            if (!isAccepted) '': 'Accept request and assign an intake later',
            ...sessionLabels,
          },
          required: isAccepted,
        ),
      ],
    );
    if (saved != null) await _load();
  }

  Future<void> _deleteScholarship(Map<String, dynamic> scholarship) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete scholarship?'),
        content: Text(
          'Delete ${scholarship['title']}? Listings with applicant history cannot be deleted; close them to preserve those records.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _action('/scholarships/${scholarship['id']}', method: 'DELETE');
    }
  }

  Future<void> _deleteTrainingCenter(Map<String, dynamic> center) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete training center?'),
        content: Text(
          'Delete ${center['name']}? Centers with linked accounts, programs, sessions, or scholarships must be deactivated instead.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _action('/training-centers/${center['id']}', method: 'DELETE');
    }
  }

  Future<void> _deleteJobOpportunity(Map<String, dynamic> job) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete job opening?'),
        content: Text(
          'Delete ${job['title']}? Openings with applications or career seeker matches must be closed instead.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _action('/opportunities/${job['id']}', method: 'DELETE');
    }
  }

  Future<void> _deleteTrainingSession(Map<String, dynamic> session) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete training session?'),
        content: Text(
          'Delete ${session['program']?['title'] ?? 'this session'}? Sessions with application history must be closed instead.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _action('/training-sessions/${session['id']}', method: 'DELETE');
    }
  }

  Future<void> _deleteAssessmentRecord(Map<String, dynamic> record) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Delete assessment record?'),
        content: const Text(
          'Permanently delete this assessment result and notes?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext, false),
            child: const Text('Cancel'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(dialogContext, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await _action('/assessment-records/${record['id']}', method: 'DELETE');
    }
  }

  Future<void> _reviewScholarshipApplication(
    Map<String, dynamic> application,
    String stage,
    String label,
  ) async {
    final saved = await openWorkspaceForm(
      context,
      title: '$label approval',
      path: '/scholarship-applications/${application['id']}',
      method: 'PUT',
      fields: [
        WorkspaceField(
          'stage',
          'Approval stage',
          options: [stage],
          required: true,
        ),
        const WorkspaceField(
          'decision',
          'Decision',
          options: ['approved', 'failed'],
          optionLabels: {'approved': 'Approved', 'failed': 'Failed'},
          required: true,
        ),
        const WorkspaceField(
          'notes',
          'Note for Career Seeker',
          multiline: true,
        ),
      ],
    );
    if (saved != null) _load();
  }

  bool _hasScholarshipApplication(int scholarshipId) => _list('myApplications')
      .any(
        (application) =>
            (application['scholarship'] as Map?)?['id'] == scholarshipId,
      );

  String _scholarshipApplicationLabel(Object? status) => switch (status) {
    'approved' => 'Approved',
    'cancelled' => 'Canceled',
    'rejected' => 'Failed',
    _ => 'Pending',
  };

  String _approvalLabel(Object? status) => switch (status) {
    'approved' => 'Approved',
    'failed' => 'Failed',
    'cancelled' => 'Canceled',
    'pending' => 'Pending',
    _ => 'Not recorded',
  };

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

  Future<void> _applyForJob(Map<String, dynamic> job) async {
    final saved = await openWorkspaceForm(
      context,
      title: 'Apply for ${job['title'] ?? 'this job'}',
      path: '/opportunities/${job['id']}/applications',
      fields: const [
        WorkspaceField(
          'qualification_summary',
          'Your qualifications for this job',
          required: true,
          multiline: true,
          minLength: 20,
          maxLength: 3000,
        ),
        WorkspaceField(
          'application_message',
          'Message to PESO (optional)',
          multiline: true,
          maxLength: 3000,
        ),
      ],
    );
    if (saved != null) await _load();
  }

  Future<void> _reviewJobApplication(
    Map<String, dynamic> application,
    String status,
  ) async {
    final fields = <WorkspaceField>[
      WorkspaceField(
        'status',
        'Decision',
        options: [status],
        optionLabels: {
          'reviewing': 'Under review',
          'hired': 'Mark hired',
          'not_selected': 'Not selected',
        },
        required: true,
      ),
      if (status == 'not_selected')
        const WorkspaceField(
          'decision_reason',
          'Reason for not selecting',
          required: true,
          multiline: true,
          minLength: 5,
          maxLength: 3000,
        ),
    ];
    final saved = await openWorkspaceForm(
      context,
      title: 'Update job application',
      path: '/job-applications/${application['id']}',
      method: 'PUT',
      fields: fields,
      initial: {'status': status},
    );
    if (saved != null) await _load();
  }

  String _title(Map<String, dynamic> item) => switch (widget.section) {
    'Job applications' =>
      '${(item['job'] as Map?)?['title'] ?? 'Job application'}',
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
    'Job applications' => [
      'Applicant: ${(item['user'] as Map?)?['name'] ?? 'Career Seeker'}',
      'Status: ${item['status'] ?? 'submitted'}',
      'Qualifications: ${item['qualification_summary'] ?? 'Not provided'}',
      if (item['application_message'] != null &&
          '${item['application_message']}'.isNotEmpty)
        'Message: ${item['application_message']}',
      if (item['decision_reason'] != null &&
          '${item['decision_reason']}'.isNotEmpty)
        'PESO feedback: ${item['decision_reason']}',
    ].join('\n'),
    'Jobs' =>
      '${item['employer_name'] ?? (item['employer'] as Map?)?['name'] ?? ''} · ${item['location'] ?? ''}\n${item['description'] ?? ''}',
    'Career paths' => [
      '${item['description'] ?? ''}',
      if (item['education_level'] != null)
        'Education or entry level: ${item['education_level']}',
      if (item['source_note'] != null) 'Source: ${item['source_note']}',
      if (_role == 'admin') 'Status: ${item['status'] ?? 'draft'}',
    ].where((line) => line.isNotEmpty).join('\n'),
    'Scholarships' => [
      '${item['provider'] ?? ''}',
      '${item['description'] ?? ''}',
      if ((item['center'] as Map?)?['name'] != null)
        'TESDA center: ${(item['center'] as Map?)?['type'] ?? 'TESDA'} · ${(item['center'] as Map)['name']}',
      if ((item['training_program'] as Map?)?['title'] != null)
        'Course: ${(item['training_program'] as Map)['title']}',
      if (item['slot_limit'] != null)
        'Available slots: ${item['is_full'] == true ? 'Fully occupied' : '${item['available_slots'] ?? 0} of ${item['slot_limit']}'}',
      if (item['slot_limit'] == null)
        'Availability: Not confirmed by the provider.',
      if (item['center_approval_status'] != null ||
          item['trainer_approval_status'] != null) ...[
        'Center approval: ${item['center_approval_status'] ?? 'pending'}${(item['center_approver'] as Map?)?['name'] == null ? '' : ' · ${(item['center_approver'] as Map)['name']}'}',
        'Trainer approval: ${item['trainer_approval_status'] ?? 'pending'}${(item['trainer_approver'] as Map?)?['name'] == null ? '' : ' · ${(item['trainer_approver'] as Map)['name']}'}',
      ] else if (item['status'] == 'published')
        'TESDA approval history was not recorded for this existing listing.',
      'Who can apply: ${item['eligibility'] ?? 'Not specified by provider'}',
      'Requirements: ${item['requirements'] ?? 'Not specified by provider'}',
      'Assessment: ${item['assessment_type'] ?? 'Not specified by provider'}',
      '${item['assessment_details'] ?? ''}',
      if (item['deadline'] != null)
        'Deadline: ${formatTuklasDate(item['deadline'])}',
      'Source: ${item['source_url'] ?? 'Not specified by provider'}',
    ].where((line) => line.isNotEmpty).join('\n'),
    'Centers' => '${item['type'] ?? ''} · ${item['address'] ?? ''}',
    'Sessions' =>
      '${(item['center'] as Map?)?['name'] ?? ''} · ${formatTuklasDate(item['starts_at'], includeTime: true)} – ${formatTuklasDate(item['ends_at'], includeTime: true)}\n${item['accepted_count'] ?? 0} of ${item['capacity'] ?? 0} places accepted',
    'Trainers' => '${(item['program'] as Map?)?['title'] ?? ''}',
    'Applications' =>
      '${(item['user'] as Map?)?['name'] ?? ''} · ${item['status'] ?? ''}',
    'Assessments' =>
      '${((item['application'] as Map?)?['user'] as Map?)?['name'] ?? ''} · ${item['status'] ?? ''} · ${item['result'] ?? 'Pending'}${item['scheduled_at'] == null ? '' : ' · ${formatTuklasDate(item['scheduled_at'], includeTime: true)}'}',
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
      floatingActionButton:
          _canManage &&
              widget.section != 'Applications' &&
              widget.section != 'Job applications'
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
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          '${(application['scholarship'] as Map?)?['title'] ?? 'Scholarship'} · ${_scholarshipApplicationLabel(application['status'])}',
                          style: Theme.of(context).textTheme.titleSmall,
                        ),
                        Text(
                          'Training center approval: ${_approvalLabel(application['center_approval_status'])}${(application['center_approver'] as Map?)?['name'] == null ? '' : ' · ${(application['center_approver'] as Map)['name']}'}',
                        ),
                        if (application['center_approval_notes'] != null)
                          Text('${application['center_approval_notes']}'),
                        Text(
                          'TESDA trainer approval: ${_approvalLabel(application['trainer_approval_status'])}${(application['trainer_approver'] as Map?)?['name'] == null ? '' : ' · ${(application['trainer_approver'] as Map)['name']}'}',
                        ),
                        if (application['trainer_approval_notes'] != null)
                          Text('${application['trainer_approval_notes']}'),
                        Text(
                          '${application['review_notes'] ?? application['application_message'] ?? ''}',
                        ),
                        if (application['status'] == 'submitted' ||
                            application['status'] == 'reviewing')
                          TextButton(
                            onPressed: _busy
                                ? null
                                : () => _action(
                                    '/scholarship-applications/${application['id']}',
                                    method: 'DELETE',
                                  ),
                            child: const Text('Cancel application'),
                          ),
                      ],
                    ),
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
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          title: Text(
                            '${(application['scholarship'] as Map?)?['title'] ?? 'Scholarship'} · ${_scholarshipApplicationLabel(application['status'])}',
                          ),
                          subtitle: Text(
                            '${(application['user'] as Map?)?['name'] ?? 'Applicant'} · ${application['application_message'] ?? ''}\nCenter: ${_approvalLabel(application['center_approval_status'])} · Trainer: ${_approvalLabel(application['trainer_approval_status'])}',
                          ),
                          isThreeLine: true,
                        ),
                        Wrap(
                          spacing: 8,
                          children: [
                            if (application['can_approve_center'] == true)
                              TextButton.icon(
                                onPressed: _busy
                                    ? null
                                    : () => _reviewScholarshipApplication(
                                        application,
                                        'center',
                                        'Training center',
                                      ),
                                icon: const Icon(Icons.apartment_outlined),
                                label: const Text('Center decision'),
                              ),
                            if (application['can_approve_trainer'] == true)
                              TextButton.icon(
                                onPressed: _busy
                                    ? null
                                    : () => _reviewScholarshipApplication(
                                        application,
                                        'trainer',
                                        'TESDA trainer',
                                      ),
                                icon: const Icon(Icons.school_outlined),
                                label: const Text('Trainer decision'),
                              ),
                          ],
                        ),
                      ],
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
                      const SizedBox(height: 8),
                      Text(_details(item)),
                      if (widget.section == 'Job applications' &&
                          _role == 'admin' &&
                          [
                            'submitted',
                            'reviewing',
                          ].contains(item['status'])) ...[
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 4,
                          children: [
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _reviewJobApplication(
                                      item,
                                      'reviewing',
                                    ),
                              child: const Text('Under review'),
                            ),
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _reviewJobApplication(item, 'hired'),
                              child: const Text('Mark hired'),
                            ),
                            TextButton(
                              onPressed: _busy
                                  ? null
                                  : () => _reviewJobApplication(
                                      item,
                                      'not_selected',
                                    ),
                              child: const Text('Not selected'),
                            ),
                          ],
                        ),
                      ],
                      if (widget.section == 'Jobs' && _role == 'youth')
                        Builder(
                          builder: (context) {
                            final applications = item['applications'];
                            final application =
                                applications is List && applications.isNotEmpty
                                ? applications.first as Map?
                                : null;
                            if (application != null) {
                              final status =
                                  '${application['status'] ?? 'submitted'}';
                              final label = switch (status) {
                                'reviewing' => 'Under review by PESO',
                                'hired' => 'Hired',
                                'not_selected' => 'Not selected',
                                _ => 'Application submitted',
                              };
                              return Padding(
                                padding: const EdgeInsets.only(top: 8),
                                child: Text(
                                  application['decision_reason'] == null
                                      ? label
                                      : '$label · ${application['decision_reason']}',
                                ),
                              );
                            }
                            final employer = item['employer'] as Map?;
                            final isVerified =
                                item['is_demo'] == true ||
                                item['employer_verified'] == true ||
                                employer?['is_verified'] == true;
                            if (item['status'] != 'published' || !isVerified) {
                              return const SizedBox.shrink();
                            }
                            return Align(
                              alignment: Alignment.centerLeft,
                              child: TextButton.icon(
                                onPressed: _busy
                                    ? null
                                    : () => _applyForJob(item),
                                icon: const Icon(Icons.send_outlined),
                                label: const Text('Apply for this job'),
                              ),
                            );
                          },
                        ),
                      if (widget.section == 'Scholarships' &&
                          _role == 'youth' &&
                          item['is_full'] == true) ...[
                        const SizedBox(height: 8),
                        const Text(
                          'Fully occupied. You may refer to these other private training centers:',
                        ),
                        for (final center in _list('privateCenters').take(5))
                          Padding(
                            padding: const EdgeInsets.only(top: 4),
                            child: Text(
                              '${center['name']}${center['address'] == null ? '' : ' · ${center['address']}'}${(center['programs'] as List?)?.isNotEmpty == true ? '\nCourses: ${(center['programs'] as List).whereType<Map>().map((program) => program['title']).take(5).join(', ')}' : ''}',
                            ),
                          ),
                        if (_list('privateCenters').isEmpty)
                          TextButton(
                            onPressed: () => launchUrl(
                              Uri.parse('https://www.tesda.gov.ph/tvi'),
                              mode: LaunchMode.externalApplication,
                            ),
                            child: const Text(
                              'Open the TESDA directory of registered training centers',
                            ),
                          ),
                      ],
                      if (widget.section == 'Scholarships' &&
                          _role == 'youth' &&
                          item['slot_limit'] == null)
                        const Padding(
                          padding: EdgeInsets.only(top: 8),
                          child: Text(
                            'Applications open after the provider confirms the course and slot count.',
                          ),
                        ),
                      if (widget.section == 'Scholarships' &&
                          item['status'] == 'review' &&
                          (item['can_approve_center'] == true ||
                              item['can_approve_trainer'] == true)) ...[
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          children: [
                            if (item['can_approve_center'] == true)
                              FilledButton.tonal(
                                onPressed: _busy
                                    ? null
                                    : () => _action(
                                        '/scholarships/${item['id']}/approval',
                                        data: {
                                          'stage': 'center',
                                          'decision': 'approved',
                                        },
                                      ),
                                child: const Text('Approve for center'),
                              ),
                            if (item['can_approve_trainer'] == true)
                              FilledButton.tonal(
                                onPressed: _busy
                                    ? null
                                    : () => _action(
                                        '/scholarships/${item['id']}/approval',
                                        data: {
                                          'stage': 'trainer',
                                          'decision': 'approved',
                                        },
                                      ),
                                child: const Text('Approve as trainer'),
                              ),
                            if (item['can_approve_center'] == true ||
                                item['can_approve_trainer'] == true)
                              TextButton(
                                onPressed: _busy
                                    ? null
                                    : () {
                                        final stage =
                                            item['can_approve_center'] == true
                                            ? 'center'
                                            : 'trainer';
                                        _action(
                                          '/scholarships/${item['id']}/approval',
                                          data: {
                                            'stage': stage,
                                            'decision': 'rejected',
                                          },
                                        );
                                      },
                                child: const Text('Return to draft'),
                              ),
                          ],
                        ),
                      ],
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
                              _programSummary(
                                Map<String, dynamic>.from(rawProgram),
                              ),
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
                      if (widget.section == 'Jobs' && _role == 'youth')
                        for (final seeker in _recommendedUsers(item))
                          if ('${seeker['id']}' ==
                              '${ApiClient.instance.user?['id']}')
                            if ((seeker['pivot'] as Map?)?['hired_at'] != null)
                              const Padding(
                                padding: EdgeInsets.only(top: 8),
                                child: Text('Hired · recorded by PESO'),
                              )
                            else if ((seeker['pivot']
                                    as Map?)?['not_selected_at'] !=
                                null)
                              Padding(
                                padding: const EdgeInsets.only(top: 8),
                                child: Text(
                                  'Not selected · ${(seeker['pivot'] as Map?)?['not_selected_reason'] ?? ''}',
                                ),
                              ),
                      if (widget.section == 'Jobs' && _role == 'admin') ...[
                        const SizedBox(height: 8),
                        Text(
                          'Career seeker matches',
                          style: Theme.of(context).textTheme.titleSmall,
                        ),
                        for (final seeker in _recommendedUsers(item))
                          Builder(
                            builder: (context) {
                              final pivot = seeker['pivot'] as Map?;
                              final hiredAt = pivot?['hired_at'];
                              final notSelectedAt = pivot?['not_selected_at'];
                              return ListTile(
                                contentPadding: EdgeInsets.zero,
                                title: Text('${seeker['name']}'),
                                subtitle: Text(
                                  hiredAt != null
                                      ? 'Hired ${formatTuklasDate(hiredAt)}'
                                      : notSelectedAt != null
                                      ? 'Not selected · ${pivot?['not_selected_reason'] ?? ''}'
                                      : '${seeker['email']}',
                                ),
                                trailing:
                                    hiredAt == null && notSelectedAt == null
                                    ? Column(
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          TextButton(
                                            onPressed: _busy
                                                ? null
                                                : () => _action(
                                                    '/opportunities/${item['id']}/hire',
                                                    data: {
                                                      'youth_email':
                                                          seeker['email'],
                                                    },
                                                  ),
                                            child: const Text('Mark hired'),
                                          ),
                                          TextButton(
                                            onPressed: _busy
                                                ? null
                                                : () => _markNotSelected(
                                                    item,
                                                    seeker,
                                                  ),
                                            child: const Text('Not selected'),
                                          ),
                                        ],
                                      )
                                    : null,
                              );
                            },
                          ),
                      ],
                      if (_canManage &&
                          widget.section != 'Assessments' &&
                          widget.section != 'Trainers')
                        TextButton(
                          onPressed: _busy ? null : () => _edit(item),
                          child: const Text('Edit'),
                        ),
                      if (widget.section == 'Scholarships' && _canManage)
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _deleteScholarship(item),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete scholarship'),
                        ),
                      if (widget.section == 'Centers' && _canManage)
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _deleteTrainingCenter(item),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete center'),
                        ),
                      if (widget.section == 'Jobs' && _role == 'admin')
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _deleteJobOpportunity(item),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete opening'),
                        ),
                      if (widget.section == 'Career paths' && _canManage)
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _action(
                                  '/career-paths/${item['id']}',
                                  method: 'DELETE',
                                ),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete career path'),
                        ),
                      if (widget.section == 'Sessions' && _canManage)
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _deleteTrainingSession(item),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete session'),
                        ),
                      if (widget.section == 'Assessments' && _canManage)
                        TextButton.icon(
                          onPressed: _busy
                              ? null
                              : () => _deleteAssessmentRecord(item),
                          icon: const Icon(Icons.delete_outline),
                          label: const Text('Delete assessment'),
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
                          item['is_full'] != true &&
                          item['slot_limit'] != null &&
                          item['training_program_id'] != null &&
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
                                  : () => _acceptTrainingApplication(item),
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
                      if (widget.section == 'Applications' &&
                          _role != 'youth' &&
                          item['status'] == 'accepted' &&
                          item['session'] == null &&
                          _hasAvailableTrainingIntake(item))
                        TextButton(
                          onPressed: _busy
                              ? null
                              : () => _acceptTrainingApplication(item),
                          child: const Text('Assign intake'),
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
              TkPagination(
                currentPage: _page,
                lastPage: (_response![_key] as Map)['last_page'] as int,
                onPrevious: _page > 1 && !_busy
                    ? () => _changePage(_page - 1)
                    : null,
                onNext: _page < (_response![_key] as Map)['last_page'] && !_busy
                    ? () => _changePage(_page + 1)
                    : null,
              ),
          ],
        ),
      ),
    );
  }
}
