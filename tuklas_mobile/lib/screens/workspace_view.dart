import 'dart:async';

import 'package:flutter/material.dart';

import '../api.dart';
import '../date_format.dart';
import '../ui.dart';
import '../workspace_realtime.dart';
import 'workspace_form.dart';
import 'operations_view.dart';

/// Role-specific workspace backed by the same actions as the website.
class WorkspaceView extends StatefulWidget {
  const WorkspaceView({super.key, required this.section});
  final String section;
  @override
  State<WorkspaceView> createState() => _WorkspaceViewState();
}

class _WorkspaceViewState extends State<WorkspaceView> {
  Map<String, dynamic>? _data;
  String? _error;
  bool _busy = false, _deleted = false;
  int _page = 1;
  int? _target;
  String _search = '';
  final _assessment = <String, dynamic>{};
  Timer? _syncTimer;
  bool _syncing = false;
  String get _role => '${ApiClient.instance.user?['role']}';
  bool get _centerScopedTesda =>
      _role == 'tesda_admin' &&
      ApiClient.instance.user?['training_center_id'] != null;
  String get _path => switch (widget.section) {
    'Users' => '/admin/users?page=$_page${_deleted ? '&status=deleted' : ''}',
    'My programs' => '/trainer/programs?page=$_page',
    'Training plans' =>
      '/tesda/workspace${_target == null ? '' : '?for_user=$_target'}',
    'Skills assessment' => '/assessment',
    'Notifications' => '/notifications?page=$_page',
    _ => '/security',
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
    super.dispose();
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
      final result = await ApiClient.instance.workspace(_path);
      if (mounted) setState(() => _data = result);
    } catch (_) {
      // Keep the last successful view until the next sync.
    } finally {
      _syncing = false;
    }
  }

  Future<void> _load() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final result = await ApiClient.instance.workspace(_path);
      if (!mounted) return;
      setState(() {
        _data = result;
        if (widget.section == 'Skills assessment') {
          final profile = asMap(result['profile']);
          _assessment.addAll(asMap(profile['assessment_preferences']));
          _assessment['skills'] = List<String>.from(
            asList(profile['skills'])
                .where((skill) => asList(result['skills']).contains(skill)),
          );
          _assessment['interests'] = List<String>.from(
            asList(profile['interests']),
          );
        }
      });
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException
              ? e.message
              : 'Could not load. Check your connection.',
        );
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _act(
    String path, {
    String method = 'POST',
    Map<String, dynamic>? data,
    String? confirm,
  }) async {
    if (confirm != null) {
      final accepted = await showDialog<bool>(
        context: context,
        builder: (c) => AlertDialog(
          title: Text(confirm),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(c, false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.pop(c, true),
              child: const Text('Confirm'),
            ),
          ],
        ),
      );
      if (accepted != true) return;
    }
    if (!mounted) return;
    setState(() => _busy = true);
    try {
      final result = await ApiClient.instance.workspace(
        path,
        method: method,
        data: data,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('${result['message'] ?? 'Saved.'}')),
      );
      await _load();
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e is ApiException ? e.message : 'Could not save. Try again.';
          _busy = false;
        });
      }
    }
  }

  Future<void> _form(
    String title,
    String path,
    List<WorkspaceField> fields, {
    String method = 'POST',
    Map<String, dynamic> initial = const {},
  }) async {
    final result = await openWorkspaceForm(
      context,
      title: title,
      path: path,
      fields: fields,
      method: method,
      initial: initial,
    );
    if (result == null || !mounted) return;
    if (result['user'] is Map) ApiClient.instance.user = asMap(result['user']);
    if (result['manual_setup_key'] != null ||
        result['recovery_codes'] != null) {
      await showDialog<void>(
        context: context,
        builder: (c) => AlertDialog(
          title: Text(
            result['manual_setup_key'] != null
                ? 'Add to your authenticator'
                : 'Save your recovery codes',
          ),
          content: SingleChildScrollView(
            child: SelectableText(
              result['manual_setup_key'] != null
                  ? 'Enter this key in your authenticator, then choose Confirm setup.\n\n${result['manual_setup_key']}'
                  : asList(result['recovery_codes']).join('\n'),
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(c),
              child: const Text('Done'),
            ),
          ],
        ),
      );
    }
    await _load();
  }

  Widget _button(String label, VoidCallback action) => Padding(
    padding: const EdgeInsets.only(right: 8, bottom: 8),
    child: OutlinedButton(onPressed: _busy ? null : action, child: Text(label)),
  );
  Widget _card(String title, List<Widget> children, {String? subtitle}) =>
      Padding(
        padding: const EdgeInsets.only(bottom: 16),
        child: TkCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: Theme.of(context).textTheme.titleMedium),
              if (subtitle != null) Text(subtitle),
              const SizedBox(height: 10),
              ...children,
            ],
          ),
        ),
      );
  Widget _pages(Map<String, dynamic> pagination) => Row(
    mainAxisAlignment: MainAxisAlignment.spaceBetween,
    children: [
      TextButton(
        onPressed: _page > 1 && !_busy
            ? () {
                _page--;
                _load();
              }
            : null,
        child: const Text('Previous'),
      ),
      Text('Page $_page of ${pagination['last_page'] ?? 1}'),
      TextButton(
        onPressed: _page < (pagination['last_page'] as num? ?? 1) && !_busy
            ? () {
                _page++;
                _load();
              }
            : null,
        child: const Text('Next'),
      ),
    ],
  );
  static const _passwordFields = [
    WorkspaceField('password', 'New password', secret: true, required: true),
    WorkspaceField(
      'password_confirmation',
      'Confirm password',
      secret: true,
      required: true,
    ),
  ];
  static const _currentPassword = WorkspaceField(
    'current_password',
    'Current password',
    secret: true,
    required: true,
  );
  List<Widget> _security() => [
    _card('Account', [
      _button(
        'Edit name and email',
        () => _form(
          'Account details',
          '/account',
          const [
            WorkspaceField('name', 'Full name', required: true),
            WorkspaceField('email', 'Email', required: true),
          ],
          method: 'PUT',
          initial: ApiClient.instance.user ?? {},
        ),
      ),
      _button(
        'Change password',
        () => _form('Change password', '/password', [
          _currentPassword,
          ..._passwordFields,
        ], method: 'PUT'),
      ),
      _button(
        'Send verification email',
        () => _act('/email/verification-notification'),
      ),
      _button('Refresh verification status', () async {
        try {
          await ApiClient.instance.me();
          if (mounted) await _load();
        } catch (e) {
          if (mounted) setState(() => _error = '$e');
        }
      }),
    ]),
    _card('Two-factor authentication', [
      Text(
        _data?['two_factor_enabled'] == true
            ? 'Enabled'
            : _data?['two_factor_setup_pending'] == true
            ? 'Setup needs confirmation'
            : 'Not enabled',
      ),
      const SizedBox(height: 12),
      if (_data?['two_factor_enabled'] != true) ...[
        _button(
          'Set up authenticator',
          () => _form(
            'Set up two-factor authentication',
            '/security/two-factor',
            [_currentPassword],
          ),
        ),
        if (_data?['two_factor_setup_pending'] == true)
          _button(
            'Confirm setup',
            () => _form('Confirm setup', '/security/two-factor/confirm', const [
              WorkspaceField(
                'code',
                'Six-digit authenticator code',
                required: true,
              ),
            ]),
          ),
      ] else ...[
        _button(
          'Generate new recovery codes',
          () => _form(
            'Replace recovery codes',
            '/security/two-factor/recovery-codes',
            [_currentPassword],
          ),
        ),
        _button(
          'Disable two-factor authentication',
          () => _form(
            'Disable two-factor authentication',
            '/security/two-factor',
            [_currentPassword],
            method: 'DELETE',
          ),
        ),
      ],
    ]),
  ];
  List<Widget> _notifications() {
    final pagination = asMap(_data!['notifications']);
    final rows = asList(pagination['data']);
    return [
      _button('Mark all read', () => _act('/notifications/read-all')),
      if (rows.isEmpty) const Text('No notifications yet.'),
      for (final raw in rows)
        Builder(
          builder: (context) {
            final row = asMap(raw), notice = asMap(asMap(raw)['data']);
            return _card(
              '${notice['title'] ?? 'Notification'}',
              [
                Text('${notice['message'] ?? ''}'),
                if (notice['kind'] == 'hiring' && _role == 'youth')
                  _button(
                    'View job',
                    () => Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => const OperationsView(section: 'Jobs'),
                      ),
                    ),
                  ),
                if (row['read_at'] == null)
                  _button(
                    'Mark read',
                    () => _act('/notifications/${row['id']}/read'),
                  ),
                if ('${notice['action_url'] ?? ''}'.contains(
                  '/account/password-reset',
                ))
                  _button(
                    'Reset password',
                    () => _form(
                      'Reset password',
                      '/account/password-reset',
                      _passwordFields,
                      method: 'PUT',
                    ),
                  ),
              ],
              subtitle: formatTuklasDate(row['created_at'], includeTime: true),
            );
          },
        ),
      _pages(pagination),
    ];
  }

  List<Widget> _assessmentWidgets() {
    final options = <String, dynamic>{
      'skills': _data!['skills'],
      'interests': _data!['interests'],
      ...asMap(_data!['preferenceOptions']),
    };
    return [
      const Text(
        'Your answers guide your next scan and career chat. Existing documented skills are kept.',
      ),
      for (final entry in options.entries)
        _card(entry.key.replaceAll('_', ' '), [
          if (['skills', 'interests', 'support_needs'].contains(entry.key))
            Wrap(
              spacing: 6,
              children: [
                for (final option in asList(entry.value))
                  FilterChip(
                    label: Text('$option'),
                    selected: asList(_assessment[entry.key]).contains(option),
                    onSelected: _busy
                        ? null
                        : (selected) => setState(() {
                            final values = List<dynamic>.from(
                              asList(_assessment[entry.key]),
                            );
                            selected
                                ? values.add(option)
                                : values.remove(option);
                            _assessment[entry.key] = values;
                          }),
                  ),
              ],
            )
          else
            DropdownButtonFormField<String>(
              initialValue: asList(entry.value).contains(_assessment[entry.key])
                  ? _assessment[entry.key] as String
                  : null,
              isExpanded: true,
              items: [
                for (final option in asList(entry.value))
                  DropdownMenuItem(value: '$option', child: Text('$option')),
              ],
              onChanged: (v) => _assessment[entry.key] = v,
            ),
        ]),
      for (final key in ['career_goal', 'experience_example', 'desired_skills'])
        Padding(
          padding: const EdgeInsets.only(bottom: 16),
          child: TextFormField(
            initialValue: '${_assessment[key] ?? ''}',
            maxLines: 3,
            maxLength: 500,
            decoration: InputDecoration(labelText: key.replaceAll('_', ' ')),
            onChanged: (v) => _assessment[key] = v,
          ),
        ),
      FilledButton(
        onPressed: _busy ? null : () => _act('/assessment', data: _assessment),
        child: const Text('Save assessment'),
      ),
    ];
  }

  static const _programFields = [
    WorkspaceField('title', 'Course title', required: true),
    WorkspaceField(
      'nc_level',
      'NC level',
      options: ['NC I', 'NC II', 'NC III', 'NC IV'],
    ),
    WorkspaceField('description', 'Description', multiline: true),
    WorkspaceField('duration_hours', 'Duration in hours'),
    WorkspaceField('duration_days', 'Duration in days'),
    WorkspaceField('requirements', 'Requirements', multiline: true),
    WorkspaceField('schedule_note', 'Schedule'),
    WorkspaceField('source_reference', 'Source reference'),
    WorkspaceField(
      'status',
      'Status',
      required: true,
      options: ['draft', 'published', 'archived'],
    ),
  ];
  List<Widget> _programs() {
    final pagination = asMap(_data!['programs']);
    final centers = asList(_data!['centers']);
    List<WorkspaceField> fieldsFor(String status) => [
      ..._programFields.where((field) => field.name != 'status'),
      if (!_centerScopedTesda)
        _programFields.last
      else
        WorkspaceField(
          'status',
          'Publication status (TESDA controlled)',
          required: true,
          options: [status],
        ),
      if (_role == 'tesda_admin')
        WorkspaceField(
          'training_center_id',
          'Training center',
          options: ['', ...centers.map((center) => '${asMap(center)['id']}')],
          optionLabels: {
            '': 'No center',
            for (final center in centers)
              '${asMap(center)['id']}': '${asMap(center)['name']}',
          },
        ),
    ];
    return [
      _button(
        'Add a program',
        () => _form(
          'Add program',
          '/trainer/programs',
          fieldsFor('draft'),
          initial: {'status': 'draft'},
        ),
      ),
      if (asList(pagination['data']).isEmpty) const Text('No programs yet.'),
      for (final raw in asList(pagination['data']))
        _card(
          '${raw['title']}',
          [
            _button(
              'Edit program',
              () => _form(
                'Edit program',
                '/trainer/programs/${raw['id']}',
                fieldsFor('${raw['status']}'),
                method: 'PUT',
                initial: asMap(raw),
              ),
            ),
          ],
          subtitle:
              '${raw['status']} • ${raw['nc_level'] ?? 'No NC level'} • ${raw['duration_days'] == null ? 'Duration not specified' : '${raw['duration_days']} training days'} • ${(raw['center'] as Map?)?['name'] ?? 'No center assigned'}',
        ),
      _pages(pagination),
      for (final raw in asList(_data!['teachingAssignments']))
        _card('${raw['program']?['title'] ?? 'Course'}', [
          _button(
            'Stop teaching',
            () => _act(
              '/trainer/teaching/${raw['training_program_id']}',
              method: 'DELETE',
              confirm: 'Remove this course from your teaching list?',
            ),
          ),
        ]),
      for (final raw in asList(_data!['trainerQualifications']))
        _card('${raw['title']}', [
          _button(
            'Remove qualification',
            () => _act(
              '/trainer/qualifications/${Uri.encodeComponent('${raw['qualification_id']}')}',
              method: 'DELETE',
              confirm: 'Remove this qualification from your teaching list?',
            ),
          ),
        ]),
    ];
  }

  Future<void> _editUser(Map<String, dynamic>? user) async {
    try {
      final response = user == null
          ? await ApiClient.instance.workspace('/training-centers')
          : await ApiClient.instance.workspace('/admin/users/${user['id']}');
      final details = user == null
          ? <String, dynamic>{'role': 'youth', 'is_active': true}
          : asMap(response['account']);
      final centers = asList(asMap(response['centers'])['data']).isNotEmpty
          ? asList(asMap(response['centers'])['data'])
          : asList(response['centers']);
      final profile = asMap(details['youth_profile']);
      if (!mounted) return;
      await _form(
        user == null ? 'Create account' : 'Edit account',
        '/admin/users${user == null ? '' : '/${user['id']}'}',
        [
          const WorkspaceField('first_name', 'First name', required: true),
          const WorkspaceField('last_name', 'Last name', required: true),
          const WorkspaceField('email', 'Email', required: true),
          const WorkspaceField(
            'role',
            'Role',
            required: true,
            options: ['youth', 'tesda_admin', 'admin'],
          ),
          WorkspaceField(
            'training_center_id',
            'Training center (required for representative)',
            options: ['', ...centers.map((center) => '${asMap(center)['id']}')],
            optionLabels: {
              '': 'No center',
              for (final center in centers)
                '${asMap(center)['id']}': '${asMap(center)['name']}',
            },
          ),
          const WorkspaceField(
            'is_active',
            'Active account',
            required: true,
            options: ['true', 'false'],
          ),
          const WorkspaceField('date_of_birth', 'Birth date (YYYY-MM-DD)'),
          const WorkspaceField('contact_number', 'Contact number'),
          const WorkspaceField('barangay', 'Barangay'),
          if (user == null) ..._passwordFields,
        ],
        method: user == null ? 'POST' : 'PUT',
        initial: {
          ...details,
          ...profile,
          'date_of_birth': '${details['date_of_birth'] ?? ''}'.split('T').first,
        },
      );
    } catch (e) {
      if (mounted) {
        setState(
          () => _error = e is ApiException
              ? e.message
              : 'Could not load account details.',
        );
      }
    }
  }

  List<Widget> _users() {
    final pagination = asMap(_data!['users']);
    return [
      _button('Create account', () => _editUser(null)),
      SwitchListTile(
        title: const Text('Deleted accounts'),
        value: _deleted,
        onChanged: _busy
            ? null
            : (v) {
                _deleted = v;
                _page = 1;
                _load();
              },
      ),
      if (asList(pagination['data']).isEmpty) const Text('No accounts found.'),
      for (final raw in asList(pagination['data']))
        _card(
          '${raw['name']}',
          [
            Text('${raw['email']}'),
            if (_deleted)
              _button(
                'Restore',
                () => _act(
                  '/admin/users/${raw['id']}/restore',
                  confirm: 'Restore this account?',
                ),
              )
            else
              Wrap(
                children: [
                  _button('Edit', () => _editUser(asMap(raw))),
                  _button(
                    'Request password reset',
                    () => _act(
                      '/admin/users/${raw['id']}/reset-password',
                      confirm: 'Request a password reset for this account?',
                    ),
                  ),
                  _button(
                    'Delete',
                    () => _act(
                      '/admin/users/${raw['id']}',
                      method: 'DELETE',
                      confirm: 'Delete this account? It can be restored.',
                    ),
                  ),
                ],
              ),
          ],
          subtitle:
              '${raw['role']} • ${raw['is_active'] == true ? 'Active' : 'Inactive'}',
        ),
      _pages(pagination),
    ];
  }

  Future<void> _chooseYouth() async {
    final chosen = await Navigator.push<int>(
      context,
      MaterialPageRoute(builder: (_) => const YouthPicker()),
    );
    if (chosen != null && mounted) {
      _target = chosen;
      await _load();
    }
  }

  List<Widget> _plans() {
    final choiceUser = asMap(_data!['choiceUser']);
    final choice = asMap(choiceUser['tesda_choice']);
    final qualifications = asList(
      asMap(_data!['nationalCatalog'])['qualifications'],
    );
    final teaching = asMap(_data!['trainerQualifications']);
    return [
      if (_role != 'youth') _button('Choose youth account', _chooseYouth),
      if (choiceUser.isNotEmpty)
        _card('Training plan for ${choiceUser['name']}', [
          Text(
            '${choice['qualification_title'] ?? 'No training plan selected yet.'}',
          ),
        ]),
      const Text(
        'Choosing a training plan does not enroll you. Confirm schedules and availability with the provider.',
      ),
      const SizedBox(height: 16),
      TextField(
        decoration: const InputDecoration(labelText: 'Search qualifications'),
        onChanged: (v) => setState(() => _search = v.toLowerCase()),
      ),
      const SizedBox(height: 16),
      for (final raw in qualifications.where(
        (q) =>
            q['status'] == 'current' &&
            '${q['title']}'.toLowerCase().contains(_search),
      ))
        _card('${raw['title']}', [
          Wrap(
            children: [
              if (_role == 'youth' || _target != null)
                _button(
                  choice['qualification_id'] == raw['id']
                      ? 'Selected plan'
                      : 'Choose plan',
                  () => _act(
                    '/tesda/choice',
                    data: {
                      'qualification_id': raw['id'],
                      if (_target != null) 'user_id': _target,
                    },
                  ),
                ),
              if (_role == 'tesda_admin')
                _button(
                  teaching.containsKey('${raw['id']}')
                      ? 'Stop teaching'
                      : 'Teach qualification',
                  () => teaching.containsKey('${raw['id']}')
                      ? _act(
                          '/trainer/qualifications/${Uri.encodeComponent('${raw['id']}')}',
                          method: 'DELETE',
                          confirm: 'Remove this qualification?',
                        )
                      : _act(
                          '/trainer/qualifications',
                          data: {'qualification_id': raw['id']},
                        ),
                ),
            ],
          ),
        ], subtitle: '${raw['nc_level']}'),
      for (final group in asMap(_data!['programGroups']).values)
        for (final raw in asList(group))
          _card('${raw['title']}', [
            Text('${raw['description'] ?? ''}\n${raw['schedule_note'] ?? ''}'),
            if (_role == 'tesda_admin')
              _button(
                asMap(_data!['teachingByProgram']).containsKey('${raw['id']}')
                    ? 'Stop teaching course'
                    : 'Teach course',
                () => _act(
                  '/trainer/teaching/${raw['id']}',
                  method:
                      asMap(_data!['teachingByProgram'])
                          .containsKey('${raw['id']}')
                      ? 'DELETE'
                      : 'POST',
                ),
              ),
          ]),
    ];
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(
      title: Text(widget.section),
      actions: [
        IconButton(
          onPressed: _busy ? null : _load,
          icon: const Icon(Icons.refresh),
          tooltip: 'Refresh',
        ),
      ],
    ),
    body: _data == null && _busy
        ? const Center(child: CircularProgressIndicator())
        : ListView(
            padding: const EdgeInsets.all(16),
            children: [
              if (_busy) const LinearProgressIndicator(),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text(
                    _error!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ),
              if (_data != null)
                ...switch (widget.section) {
                  'Users' => _users(),
                  'My programs' => _programs(),
                  'Training plans' => _plans(),
                  'Skills assessment' => _assessmentWidgets(),
                  'Notifications' => _notifications(),
                  _ => _security(),
                },
            ],
          ),
  );
}

class YouthPicker extends StatefulWidget {
  const YouthPicker({super.key});
  @override
  State<YouthPicker> createState() => _YouthPickerState();
}

class _YouthPickerState extends State<YouthPicker> {
  int _page = 1;
  late Future<Map<String, dynamic>> _future = ApiClient.instance.workspace(
    '/tesda/choices',
  );
  void _load(int page) => setState(() {
    _page = page;
    _future = ApiClient.instance.workspace('/tesda/choices?page=$page');
  });
  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Choose youth account')),
    body: FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.hasError) {
          return Center(
            child: TextButton(
              onPressed: () => _load(_page),
              child: const Text('Could not load. Try again'),
            ),
          );
        }
        if (!snapshot.hasData) {
          return const Center(child: CircularProgressIndicator());
        }
        final page = asMap(snapshot.data!['youthUsers']);
        return ListView(
          children: [
            if (asList(page['data']).isEmpty)
              const ListTile(title: Text('No active youth accounts.')),
            for (final user in asList(page['data']))
              ListTile(
                title: Text('${user['name']}'),
                subtitle: Text(
                  '${user['tesda_choice']?['qualification_title'] ?? 'No plan selected'}',
                ),
                onTap: () => Navigator.pop(context, user['id']),
              ),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                TextButton(
                  onPressed: _page > 1 ? () => _load(_page - 1) : null,
                  child: const Text('Previous'),
                ),
                TextButton(
                  onPressed: _page < (page['last_page'] as num? ?? 1)
                      ? () => _load(_page + 1)
                      : null,
                  child: const Text('Next'),
                ),
              ],
            ),
          ],
        );
      },
    ),
  );
}
