import 'dart:typed_data';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'tuklas_api.dart';
import 'tuklas_theme.dart';

class TuklasScannerScreen extends StatefulWidget {
  const TuklasScannerScreen({super.key, required this.api});

  final TuklasApi api;

  @override
  State<TuklasScannerScreen> createState() => _TuklasScannerScreenState();
}

class _TuklasScannerScreenState extends State<TuklasScannerScreen> {
  List<Map<String, dynamic>> _history = [];
  PlatformFile? _file;
  Uint8List? _fileBytes;
  int _fileSize = 0;
  String _documentType = 'resume';
  bool _consent = false;
  bool _loading = true;
  bool _uploading = false;
  double? _progress;
  String? _message;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    try {
      final result = await widget.api.scans();
      if (mounted) {
        setState(() => _history = List<Map<String, dynamic>>.from(result['documents'] ?? const []));
      }
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _chooseFile() async {
    setState(() {
      _message = null;
      _error = null;
    });
    try {
      final file = await FilePicker.pickFile(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
      );
      if (file == null) return;
      final size = await file.length() ?? file.lengthSync() ?? 0;
      if (size > 10 * 1024 * 1024) {
        setState(() {
          _file = null;
          _fileBytes = null;
          _error = 'Choose a document smaller than 10 MB.';
        });
        return;
      }
      final bytes = await file.readAsBytes();
      setState(() {
        _file = file;
        _fileBytes = bytes;
        _fileSize = size;
      });
    } catch (error) {
      setState(() => _error = TuklasApi.errorMessage(error));
    }
  }

  Future<void> _scan() async {
    final file = _file;
    final bytes = _fileBytes;
    if (file == null || bytes == null || !_consent || _uploading) return;
    setState(() {
      _uploading = true;
      _progress = 0;
      _message = null;
      _error = null;
    });
    try {
      final document = await widget.api.uploadScan(
        documentType: _documentType,
        file: file,
        bytes: bytes,
        onSendProgress: (sent, total) {
          if (total > 0 && mounted) setState(() => _progress = sent / total);
        },
      );
      if (mounted) {
        setState(() {
          _history.insert(0, document);
          _file = null;
          _fileBytes = null;
          _fileSize = 0;
          _consent = false;
          _message = 'Document scanned. Review the insights below.';
        });
      }
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return RefreshIndicator(
      onRefresh: _loadHistory,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
        children: [
          const _Eyebrow(text: 'GOOGLE GEMINI AI'),
          const SizedBox(height: 8),
          Text('Make your experience easier to show.', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w500)),
          const SizedBox(height: 7),
          Text(
            'Upload a resume or certificate. Gemini summarizes qualifications and suggests practical next steps.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: colors.onSurfaceVariant, height: 1.5),
          ),
          const SizedBox(height: 17),
          Container(
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: Theme.of(context).cardTheme.color,
              border: Border.all(color: colors.outline),
              borderRadius: BorderRadius.circular(13),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                DropdownButtonFormField<String>(
                  initialValue: _documentType,
                  decoration: const InputDecoration(labelText: 'Document type'),
                  items: const [
                    DropdownMenuItem(value: 'resume', child: Text('Resume')),
                    DropdownMenuItem(value: 'certification', child: Text('Certificate')),
                  ],
                  onChanged: _uploading ? null : (value) => setState(() => _documentType = value ?? 'resume'),
                ),
                const SizedBox(height: 12),
                OutlinedButton.icon(
                  onPressed: _uploading ? null : _chooseFile,
                  icon: const Icon(Icons.upload_file_outlined),
                  label: Text(_file?.name ?? 'Choose a document'),
                ),
                const SizedBox(height: 5),
                Text('PDF or image · up to 10 MB · one document at a time', style: Theme.of(context).textTheme.bodySmall?.copyWith(color: colors.onSurfaceVariant)),
                if (_file != null) ...[
                  const SizedBox(height: 9),
                  Text('${_file!.name} · ${(_fileSize / 1024).ceil()} KB', maxLines: 1, overflow: TextOverflow.ellipsis, style: Theme.of(context).textTheme.bodySmall),
                ],
                const SizedBox(height: 12),
                CheckboxListTile(
                  contentPadding: EdgeInsets.zero,
                  controlAffinity: ListTileControlAffinity.leading,
                  value: _consent,
                  onChanged: _uploading ? null : (value) => setState(() => _consent = value ?? false),
                  title: const Text('I agree to send this file to Google Gemini for analysis.', style: TextStyle(fontSize: 12, height: 1.4)),
                ),
                if (_uploading) ...[
                  const SizedBox(height: 8),
                  LinearProgressIndicator(value: _progress),
                  const SizedBox(height: 6),
                  const Text('Uploading and analyzing…', style: TextStyle(fontSize: 12)),
                ],
                const SizedBox(height: 8),
                FilledButton.icon(
                  onPressed: _file != null && _consent && !_uploading ? _scan : null,
                  icon: const Icon(Icons.auto_awesome, size: 18),
                  label: Text(_uploading ? 'Scanning…' : 'Scan document'),
                ),
              ],
            ),
          ),
          if (_error != null) ...[const SizedBox(height: 12), _InlineMessage(text: _error!, error: true)],
          if (_message != null) ...[const SizedBox(height: 12), _InlineMessage(text: _message!, error: false)],
          const SizedBox(height: 14),
          _InlineMessage(
            text: 'Scanning is optional. Review and confirm extracted details before saving them to your profile. Avoid uploading information you are not comfortable sharing with Google Gemini.',
            error: false,
            quiet: true,
          ),
          const SizedBox(height: 24),
          const _SectionHeading(title: 'Document insights', trailing: 'YOUR SCANS'),
          const SizedBox(height: 8),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(18), child: CircularProgressIndicator()))
          else if (_history.isEmpty)
            const _EmptyPanel(icon: Icons.document_scanner_outlined, title: 'Your completed scans will appear here.')
          else
            for (final scan in _history) _ScanResultCard(scan: scan),
        ],
      ),
    );
  }
}

class _ScanResultCard extends StatelessWidget {
  const _ScanResultCard({required this.scan});

  final Map<String, dynamic> scan;

  @override
  Widget build(BuildContext context) {
    final status = '${scan['status'] ?? 'processing'}';
    final color = status == 'completed' || status == 'done'
        ? TuklasColors.good
        : status == 'failed'
            ? Theme.of(context).colorScheme.error
            : TuklasColors.warn;
    final analysis = scan['analysis'];
    return Card(
      margin: const EdgeInsets.only(bottom: 9),
      child: Padding(
        padding: const EdgeInsets.all(13),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                const Icon(Icons.description_outlined, color: TuklasColors.violet),
                const SizedBox(width: 9),
                Expanded(child: Text('${scan['original_name'] ?? 'Document'}', maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w500))),
                Text(status, style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w600)),
              ],
            ),
            if (analysis is Map && analysis['summary'] is String) ...[
              const SizedBox(height: 9),
              Text(analysis['summary'] as String, style: Theme.of(context).textTheme.bodySmall?.copyWith(height: 1.5)),
            ],
            if (scan['failure_message'] is String) ...[
              const SizedBox(height: 8),
              Text(scan['failure_message'] as String, style: TextStyle(color: color, fontSize: 12)),
            ],
            if (scan['created_at'] is String) ...[
              const SizedBox(height: 6),
              Text('${scan['created_at']}'.split('T').first, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
            ],
          ],
        ),
      ),
    );
  }
}

class TuklasChatScreen extends StatefulWidget {
  const TuklasChatScreen({super.key, required this.api});

  final TuklasApi api;

  @override
  State<TuklasChatScreen> createState() => _TuklasChatScreenState();
}

class _TuklasChatScreenState extends State<TuklasChatScreen> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  final List<Map<String, String>> _messages = [
    {'role': 'model', 'text': 'Ask about careers, skills training, or a practical next step.'},
  ];
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _send([String? prompt]) async {
    final text = (prompt ?? _input.text).trim();
    if (text.isEmpty || _sending) return;
    setState(() {
      _error = null;
      _sending = true;
      _messages.add({'role': 'user', 'text': text});
      if (_messages.length > 11) _messages.removeRange(1, 3);
      _input.clear();
    });
    try {
      final reply = await widget.api.sendChat(_messages);
      if (mounted) setState(() => _messages.add({'role': 'model', 'text': reply}));
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) {
        setState(() => _sending = false);
        WidgetsBinding.instance.addPostFrameCallback((_) {
          if (_scroll.hasClients) _scroll.animateTo(_scroll.position.maxScrollExtent, duration: const Duration(milliseconds: 220), curve: Curves.easeOut);
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(title: const Text('Career assistant')),
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: ListView.builder(
                controller: _scroll,
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 18),
                itemCount: _messages.length + (_messages.length == 1 ? 1 : 0),
                itemBuilder: (context, index) {
                  if (_messages.length == 1 && index == 1) {
                    return _PromptSuggestions(onTap: _send);
                  }
                  final message = _messages[index];
                  final isUser = message['role'] == 'user';
                  return Align(
                    alignment: isUser ? Alignment.centerRight : Alignment.centerLeft,
                    child: Container(
                      constraints: const BoxConstraints(maxWidth: 310),
                      margin: const EdgeInsets.only(bottom: 9),
                      padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 10),
                      decoration: BoxDecoration(
                        color: isUser ? TuklasColors.violet : Theme.of(context).cardTheme.color,
                        border: isUser ? null : Border.all(color: colors.outline),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Text(message['text'] ?? '', style: TextStyle(color: isUser ? Colors.white : colors.onSurface, height: 1.45)),
                    ),
                  );
                },
              ),
            ),
            if (_error != null) Padding(padding: const EdgeInsets.symmetric(horizontal: 14), child: _InlineMessage(text: _error!, error: true)),
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 9, 12, 6),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _input,
                      enabled: !_sending,
                      textInputAction: TextInputAction.send,
                      maxLength: 2000,
                      decoration: const InputDecoration(hintText: 'Ask something…', counterText: ''),
                      onSubmitted: (_) => _send(),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    tooltip: 'Send message',
                    onPressed: _sending ? null : _send,
                    icon: _sending ? const SizedBox.square(dimension: 18, child: CircularProgressIndicator(strokeWidth: 2)) : const Icon(Icons.arrow_upward),
                  ),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 8),
              child: Text('Messages are sent to Google Gemini. Avoid sharing private information.', textAlign: TextAlign.center, style: Theme.of(context).textTheme.labelSmall?.copyWith(color: colors.onSurfaceVariant)),
            ),
          ],
        ),
      ),
    );
  }
}

class _PromptSuggestions extends StatelessWidget {
  const _PromptSuggestions({required this.onTap});

  final ValueChanged<String> onTap;

  @override
  Widget build(BuildContext context) {
    const prompts = [
      'Help me explore careers that match my interests.',
      'How can I find skills training that fits my goals?',
      'Help me make a practical plan for my next step.',
      'What skills should I build for the kind of work I want?',
    ];
    return Padding(
      padding: const EdgeInsets.only(top: 12, bottom: 8),
      child: Wrap(
        spacing: 7,
        runSpacing: 7,
        children: [
          for (final prompt in prompts)
            ActionChip(
              label: Text(prompt, maxLines: 2, overflow: TextOverflow.ellipsis),
              onPressed: () => onTap(prompt),
              side: BorderSide(color: Theme.of(context).colorScheme.outline),
            ),
        ],
      ),
    );
  }
}

class TuklasCatalogScreen extends StatefulWidget {
  const TuklasCatalogScreen({super.key, required this.api});

  final TuklasApi api;

  @override
  State<TuklasCatalogScreen> createState() => _TuklasCatalogScreenState();
}

class _TuklasCatalogScreenState extends State<TuklasCatalogScreen> {
  Map<String, dynamic>? _catalog;
  String? _error;
  bool _loading = true;

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
      final result = await widget.api.catalog();
      if (mounted) setState(() => _catalog = result);
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final programs = List<Map<String, dynamic>>.from(_catalog?['programs'] ?? const []);
    final resources = List<Map<String, dynamic>>.from(_catalog?['resources'] ?? const []);
    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
        children: [
          const _Eyebrow(text: 'TESDA LINGAYEN · PANGASINAN'),
          const SizedBox(height: 8),
          Text('Explore training and services.', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w500)),
          const SizedBox(height: 7),
          Text('Browse published local programs and official TESDA learning resources.', style: Theme.of(context).textTheme.bodyMedium?.copyWith(color: colors.onSurfaceVariant, height: 1.5)),
          const SizedBox(height: 19),
          const _SectionHeading(title: 'TESDA Lingayen programs', trailing: 'LOCAL CATALOG'),
          const SizedBox(height: 8),
          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator()))
          else if (_error != null)
            _RetryPanel(message: _error!, onRetry: _load)
          else if (programs.isEmpty)
            const _EmptyPanel(icon: Icons.school_outlined, title: 'No local programs have been published yet.')
          else
            for (final program in programs) _ProgramCard(program: program),
          const SizedBox(height: 22),
          const _SectionHeading(title: 'Official TESDA resources', trailing: 'OPEN EXTERNAL'),
          const SizedBox(height: 8),
          for (final resource in resources)
            ListTile(
              contentPadding: const EdgeInsets.symmetric(vertical: 2),
              leading: const Icon(Icons.open_in_new, color: TuklasColors.violet),
              title: Text('${resource['title'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w500)),
              trailing: const Icon(Icons.arrow_forward_ios, size: 14),
              onTap: () async {
                final url = Uri.tryParse('${resource['url'] ?? ''}');
                if (url != null) await launchUrl(url, mode: LaunchMode.externalApplication);
              },
            ),
          const SizedBox(height: 8),
          _InlineMessage(text: '${_catalog?['notice'] ?? 'Confirm program details with TESDA or the provider.'}', error: false, quiet: true),
        ],
      ),
    );
  }
}

class _ProgramCard extends StatelessWidget {
  const _ProgramCard({required this.program});

  final Map<String, dynamic> program;

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 9),
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(child: Text('${program['nc_level'] ?? 'TESDA training program'}', style: const TextStyle(color: TuklasColors.violet, fontSize: 11, fontWeight: FontWeight.w600))),
                if (program['last_verified_at'] is String) Text('Checked ${program['last_verified_at']}', style: Theme.of(context).textTheme.labelSmall),
              ],
            ),
            const SizedBox(height: 6),
            Text('${program['title'] ?? ''}', style: Theme.of(context).textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w500)),
            if (program['description'] is String && (program['description'] as String).isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(program['description'] as String, style: Theme.of(context).textTheme.bodySmall?.copyWith(height: 1.45)),
            ],
            if (program['duration_hours'] != null || program['schedule_note'] != null) ...[
              const SizedBox(height: 9),
              Wrap(
                spacing: 8,
                runSpacing: 5,
                children: [
                  if (program['duration_hours'] != null) Text('${program['duration_hours']} training hours', style: Theme.of(context).textTheme.labelSmall),
                  if (program['schedule_note'] is String) Text(program['schedule_note'] as String, style: Theme.of(context).textTheme.labelSmall),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class TuklasProfileScreen extends StatefulWidget {
  const TuklasProfileScreen({
    super.key,
    required this.api,
    required this.user,
    required this.onUserUpdated,
    required this.onSignOut,
  });

  final TuklasApi api;
  final Map<String, dynamic> user;
  final ValueChanged<Map<String, dynamic>> onUserUpdated;
  final VoidCallback onSignOut;

  @override
  State<TuklasProfileScreen> createState() => _TuklasProfileScreenState();
}

class _TuklasProfileScreenState extends State<TuklasProfileScreen> {
  final _accountForm = GlobalKey<FormState>();
  final _youthForm = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _dob = TextEditingController();
  final _barangay = TextEditingController();
  final _phone = TextEditingController();
  final _livelihood = TextEditingController();
  final _currentPassword = TextEditingController();
  final _newPassword = TextEditingController();
  final _currentPasswordForSecurity = TextEditingController();
  final _code = TextEditingController();
  String? _education;
  String? _employment;
  bool _loading = true;
  bool _saving = false;
  bool _isYouth = false;
  bool _twoFactorEnabled = false;
  String? _error;
  String? _message;
  String? _setupKey;
  List<dynamic> _recoveryCodes = [];

  static const _educationOptions = [
    'Elementary graduate',
    'Junior high school undergraduate',
    'Junior high school graduate',
    'Senior high school undergraduate',
    'Senior high school graduate',
    'Vocational or TVET graduate',
    'College undergraduate',
    'College graduate',
    'Postgraduate',
  ];
  static const _employmentOptions = ['Student', 'Employed', 'Self-employed', 'Unemployed', 'Out of school'];

  @override
  void initState() {
    super.initState();
    _name.text = '${widget.user['name'] ?? ''}';
    _email.text = '${widget.user['email'] ?? ''}';
    _isYouth = widget.user['role'] == 'youth';
    _load();
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _dob.dispose();
    _barangay.dispose();
    _phone.dispose();
    _livelihood.dispose();
    _currentPassword.dispose();
    _newPassword.dispose();
    _currentPasswordForSecurity.dispose();
    _code.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    try {
      if (_isYouth) {
        final profile = await widget.api.profile();
        final user = Map<String, dynamic>.from(profile['user'] ?? const {});
        final details = Map<String, dynamic>.from(profile['profile'] ?? const {});
        _name.text = '${user['name'] ?? _name.text}';
        _email.text = '${user['email'] ?? _email.text}';
        _dob.text = '${user['date_of_birth'] ?? ''}';
        _barangay.text = '${details['barangay'] ?? ''}';
        _phone.text = '${details['contact_number'] ?? ''}';
        _livelihood.text = '${details['livelihood_interests'] ?? ''}';
        _education = details['educational_attainment'] as String?;
        _employment = details['employment_status'] as String?;
      }
      final security = await widget.api.security();
      if (mounted) setState(() => _twoFactorEnabled = security['two_factor_enabled'] == true);
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _saveAccount() async {
    if (!(_accountForm.currentState?.validate() ?? false)) return;
    await _run(() async {
      final user = await widget.api.saveAccount(name: _name.text.trim(), email: _email.text.trim());
      widget.onUserUpdated(user);
      _message = user['email_verified'] == false
          ? 'Account saved. Verify your new email address to continue using all features.'
          : 'Account details saved.';
    });
  }

  Future<void> _saveYouthProfile() async {
    if (!(_youthForm.currentState?.validate() ?? false)) return;
    await _run(() async {
      await widget.api.saveYouthProfile({
        'date_of_birth': _dob.text.trim(),
        'barangay': _barangay.text.trim(),
        'contact_number': _phone.text.trim(),
        'educational_attainment': _education,
        'employment_status': _employment,
        'livelihood_interests': _livelihood.text.trim(),
        if (_isMinor) ...{
          'guardian_name': _guardianName.text.trim(),
          'guardian_relationship': _guardianRelationship.text.trim(),
          'guardian_contact': _guardianContact.text.trim(),
        },
      });
      _message = 'Your youth details were saved.';
    });
  }

  final _guardianName = TextEditingController();
  final _guardianRelationship = TextEditingController();
  final _guardianContact = TextEditingController();

  bool get _isMinor {
    final date = DateTime.tryParse(_dob.text);
    if (date == null) return false;
    final today = DateTime.now();
    var age = today.year - date.year;
    if (today.month < date.month || (today.month == date.month && today.day < date.day)) age--;
    return age < 18;
  }

  Future<void> _changePassword() async {
    await _run(() async {
      await widget.api.changePassword(currentPassword: _currentPassword.text, newPassword: _newPassword.text);
      _currentPassword.clear();
      _newPassword.clear();
      _message = 'Password updated. Other devices have been signed out.';
    });
  }

  Future<void> _enableTwoFactor() async {
    await _run(() async {
      final result = await widget.api.enableTwoFactor(_currentPasswordForSecurity.text);
      _setupKey = result['manual_setup_key'] as String?;
      _currentPasswordForSecurity.clear();
      _message = 'Add this account to your authenticator, then enter the 6-digit code to confirm.';
    });
  }

  Future<void> _confirmTwoFactor() async {
    await _run(() async {
      final result = await widget.api.confirmTwoFactor(_code.text.trim());
      _twoFactorEnabled = result['two_factor_enabled'] == true;
      _recoveryCodes = List<dynamic>.from(result['recovery_codes'] ?? const []);
      _code.clear();
      _setupKey = null;
      _message = 'Two-factor authentication is enabled. Save your recovery codes somewhere safe.';
    });
  }

  Future<void> _rotateRecoveryCodes() async {
    await _run(() async {
      _recoveryCodes = await widget.api.regenerateRecoveryCodes(_currentPasswordForSecurity.text);
      _currentPasswordForSecurity.clear();
      _message = 'New recovery codes generated. Previous codes no longer work.';
    });
  }

  Future<void> _disableTwoFactor() async {
    await _run(() async {
      await widget.api.disableTwoFactor(_currentPasswordForSecurity.text);
      _currentPasswordForSecurity.clear();
      _twoFactorEnabled = false;
      _message = 'Two-factor authentication is disabled.';
    });
  }

  Future<void> _run(Future<void> Function() operation) async {
    setState(() {
      _saving = true;
      _error = null;
      _message = null;
    });
    try {
      await operation();
    } catch (error) {
      _error = TuklasApi.errorMessage(error);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) return const Center(child: CircularProgressIndicator());
    return ListView(
      padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
      children: [
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const _Eyebrow(text: 'ACCOUNT'),
                  const SizedBox(height: 6),
                  Text('Profile and security', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w500)),
                ],
              ),
            ),
            IconButton(tooltip: 'Sign out', onPressed: widget.onSignOut, icon: const Icon(Icons.logout)),
          ],
        ),
        const SizedBox(height: 16),
        if (_error != null) _InlineMessage(text: _error!, error: true),
        if (_message != null) ...[
          _InlineMessage(text: _message!, error: false),
          const SizedBox(height: 9),
        ],
        _ProfileSection(
          title: 'Account details',
          child: Form(
            key: _accountForm,
            child: Column(
              children: [
                TextFormField(controller: _name, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(labelText: 'Full name'), validator: _required),
                const SizedBox(height: 11),
                TextFormField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email address'), validator: _emailRequired),
                const SizedBox(height: 11),
                Align(alignment: Alignment.centerRight, child: FilledButton(onPressed: _saving ? null : _saveAccount, child: const Text('Save account'))),
              ],
            ),
          ),
        ),
        if (_isYouth) ...[
          const SizedBox(height: 12),
          _ProfileSection(
            title: 'Youth details',
            subtitle: 'Tuklas is for youth aged 15 to 30.',
            child: Form(
              key: _youthForm,
              child: Column(
                children: [
                  TextFormField(controller: _dob, keyboardType: TextInputType.datetime, decoration: const InputDecoration(labelText: 'Date of birth', hintText: 'YYYY-MM-DD'), validator: (value) => DateTime.tryParse(value ?? '') == null ? 'Enter a valid date of birth.' : null),
                  const SizedBox(height: 11),
                  TextFormField(controller: _barangay, decoration: const InputDecoration(labelText: 'Barangay'), validator: _required),
                  const SizedBox(height: 11),
                  TextFormField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Contact number')),
                  const SizedBox(height: 11),
                  DropdownButtonFormField<String>(
                    initialValue: _educationOptions.contains(_education) ? _education : null,
                    decoration: const InputDecoration(labelText: 'Educational attainment'),
                    items: [for (final item in _educationOptions) DropdownMenuItem(value: item, child: Text(item))],
                    onChanged: (value) => setState(() => _education = value),
                  ),
                  const SizedBox(height: 11),
                  DropdownButtonFormField<String>(
                    initialValue: _employmentOptions.contains(_employment) ? _employment : null,
                    decoration: const InputDecoration(labelText: 'Current status'),
                    items: [for (final item in _employmentOptions) DropdownMenuItem(value: item, child: Text(item))],
                    onChanged: (value) => setState(() => _employment = value),
                  ),
                  const SizedBox(height: 11),
                  TextFormField(controller: _livelihood, maxLines: 3, maxLength: 1000, decoration: const InputDecoration(labelText: 'Interests and livelihood interests', hintText: 'For example: cooking, farming, electronics, selling online')),
                  if (_isMinor) ...[
                    const SizedBox(height: 8),
                    const Align(alignment: Alignment.centerLeft, child: Text('Guardian details are required for users under 18.', style: TextStyle(color: TuklasColors.violet, fontSize: 12, fontWeight: FontWeight.w600))),
                    const SizedBox(height: 10),
                    TextFormField(controller: _guardianName, decoration: const InputDecoration(labelText: 'Guardian name'), validator: _required),
                    const SizedBox(height: 10),
                    TextFormField(controller: _guardianRelationship, decoration: const InputDecoration(labelText: 'Relationship'), validator: _required),
                    const SizedBox(height: 10),
                    TextFormField(controller: _guardianContact, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Guardian contact number'), validator: _required),
                  ],
                  const SizedBox(height: 11),
                  Align(alignment: Alignment.centerRight, child: FilledButton(onPressed: _saving ? null : _saveYouthProfile, child: const Text('Save youth details'))),
                ],
              ),
            ),
          ),
        ],
        const SizedBox(height: 12),
        _ProfileSection(
          title: 'Password',
          subtitle: 'Use a strong password you do not use elsewhere.',
          child: Column(
            children: [
              TextField(controller: _currentPassword, obscureText: true, decoration: const InputDecoration(labelText: 'Current password')),
              const SizedBox(height: 10),
              TextField(controller: _newPassword, obscureText: true, decoration: const InputDecoration(labelText: 'New password')),
              const SizedBox(height: 10),
              Align(alignment: Alignment.centerRight, child: FilledButton(onPressed: _saving ? null : _changePassword, child: const Text('Update password'))),
            ],
          ),
        ),
        const SizedBox(height: 12),
        _ProfileSection(
          title: 'Two-factor authentication',
          subtitle: 'Protect your account with an authenticator code and recovery codes.',
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              if (!_twoFactorEnabled && _setupKey == null) ...[
                TextField(controller: _currentPasswordForSecurity, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm current password')),
                const SizedBox(height: 10),
                OutlinedButton.icon(onPressed: _saving ? null : _enableTwoFactor, icon: const Icon(Icons.shield_outlined), label: const Text('Set up two-factor authentication')),
              ],
              if (_setupKey != null) ...[
                const Text('Enter this key in your authenticator app:'),
                const SizedBox(height: 6),
                SelectableText(_setupKey!, style: const TextStyle(fontWeight: FontWeight.w700, letterSpacing: 1)),
                const SizedBox(height: 10),
                TextField(controller: _code, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: '6-digit code')),
                const SizedBox(height: 10),
                FilledButton(onPressed: _saving ? null : _confirmTwoFactor, child: const Text('Confirm setup')),
              ],
              if (_twoFactorEnabled) ...[
                const Row(children: [Icon(Icons.verified_user_outlined, color: TuklasColors.good), SizedBox(width: 8), Text('Two-factor authentication is on')]),
                const SizedBox(height: 10),
                TextField(controller: _currentPasswordForSecurity, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm current password')),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: [
                    OutlinedButton(onPressed: _saving ? null : _rotateRecoveryCodes, child: const Text('New recovery codes')),
                    TextButton(onPressed: _saving ? null : _disableTwoFactor, child: const Text('Turn off 2FA')),
                  ],
                ),
              ],
              if (_recoveryCodes.isNotEmpty) ...[
                const SizedBox(height: 12),
                const Text('Save these recovery codes. Each code can be used once.', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(height: 7),
                SelectableText(_recoveryCodes.join('\n'), style: const TextStyle(fontFamily: 'monospace', height: 1.6)),
              ],
              if (_saving) const LinearProgressIndicator(),
            ],
          ),
        ),
        const SizedBox(height: 14),
        if (widget.user['email_verified'] == false)
          const _InlineMessage(text: 'Your email is unverified. Resend a verification link from the sign-in screen.', error: false, quiet: true),
        const SizedBox(height: 10),
      ],
    );
  }
}

class _ProfileSection extends StatelessWidget {
  const _ProfileSection({required this.title, required this.child, this.subtitle});

  final String title;
  final String? subtitle;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Theme.of(context).cardTheme.color,
        border: Border.all(color: Theme.of(context).colorScheme.outline),
        borderRadius: BorderRadius.circular(13),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w600)),
          if (subtitle != null) ...[
            const SizedBox(height: 4),
            Text(subtitle!, style: Theme.of(context).textTheme.bodySmall?.copyWith(color: Theme.of(context).colorScheme.onSurfaceVariant)),
          ],
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _Eyebrow extends StatelessWidget {
  const _Eyebrow({required this.text});

  final String text;

  @override
  Widget build(BuildContext context) => Text(
        text,
        style: Theme.of(context).textTheme.labelSmall?.copyWith(color: TuklasColors.violet, fontWeight: FontWeight.w700),
      );
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

class _InlineMessage extends StatelessWidget {
  const _InlineMessage({required this.text, required this.error, this.quiet = false});

  final String text;
  final bool error;
  final bool quiet;

  @override
  Widget build(BuildContext context) {
    final color = error ? Theme.of(context).colorScheme.error : quiet ? Theme.of(context).colorScheme.onSurfaceVariant : TuklasColors.good;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(11),
      decoration: BoxDecoration(
        color: color.withValues(alpha: quiet ? .07 : .1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(text, style: TextStyle(color: color, fontSize: 12, height: 1.45)),
    );
  }
}

class _EmptyPanel extends StatelessWidget {
  const _EmptyPanel({required this.icon, required this.title});

  final IconData icon;
  final String title;

  @override
  Widget build(BuildContext context) => Container(
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(color: Theme.of(context).cardTheme.color, borderRadius: BorderRadius.circular(12)),
        child: Row(
          children: [
            Icon(icon, color: TuklasColors.violet),
            const SizedBox(width: 10),
            Expanded(child: Text(title, style: Theme.of(context).textTheme.bodySmall)),
          ],
        ),
      );
}

class _RetryPanel extends StatelessWidget {
  const _RetryPanel({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Column(
        children: [
          _InlineMessage(text: message, error: true),
          TextButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('Try again')),
        ],
      );
}

String? _required(String? value) => (value?.trim().isEmpty ?? true) ? 'This field is required.' : null;

String? _emailRequired(String? value) {
  final email = value?.trim() ?? '';
  return email.contains('@') ? null : 'Enter a valid email address.';
}
