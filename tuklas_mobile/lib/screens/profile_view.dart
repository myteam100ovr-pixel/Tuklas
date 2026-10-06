import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class ProfileView extends StatefulWidget {
  const ProfileView({
    super.key,
    required this.onSaved,
    required this.onLogout,
    this.loadProfile,
  });

  final VoidCallback onSaved;
  final VoidCallback onLogout;
  final Future<Map<String, dynamic>> Function()? loadProfile;

  @override
  State<ProfileView> createState() => _ProfileViewState();
}

class _ProfileViewState extends State<ProfileView> {
  late Future<Map<String, dynamic>> _future;

  final _barangay = TextEditingController();
  final _contact = TextEditingController();
  final _interests = TextEditingController();
  final _guardianName = TextEditingController();
  final _guardianRelationship = TextEditingController();
  final _guardianContact = TextEditingController();

  String? _dob;
  String? _education;
  String? _employment;

  bool _filled = false;
  bool _saving = false;
  String? _flash;
  String? _formError;
  Map<String, String> _errors = {};

  @override
  void initState() {
    super.initState();
    _future = (widget.loadProfile ?? ApiClient.instance.profile)();
  }

  @override
  void dispose() {
    _barangay.dispose();
    _contact.dispose();
    _interests.dispose();
    _guardianName.dispose();
    _guardianRelationship.dispose();
    _guardianContact.dispose();
    super.dispose();
  }

  void _fill(Map<String, dynamic> youth) {
    _dob = youth['date_of_birth'] as String?;
    _barangay.text = '${youth['barangay'] ?? ''}';
    _contact.text = '${youth['contact_number'] ?? ''}';
    _education = youth['educational_attainment'] as String?;
    _employment = youth['employment_status'] as String?;
    _interests.text = '${youth['livelihood_interests'] ?? ''}';
    _guardianName.text = '${youth['guardian_name'] ?? ''}';
    _guardianRelationship.text = '${youth['guardian_relationship'] ?? ''}';
    _guardianContact.text = '${youth['guardian_contact'] ?? ''}';
    _filled = true;
  }

  int? _age() {
    final born = _dob == null ? null : DateTime.tryParse(_dob!);
    if (born == null) return null;
    final now = DateTime.now();
    var age = now.year - born.year;
    if (now.month < born.month ||
        (now.month == born.month && now.day < born.day)) {
      age--;
    }
    return age;
  }

  bool get _minor => (_age() ?? 99) < 18;

  String _fmt(DateTime d) =>
      '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

  Future<void> _pickDob() async {
    final now = DateTime.now();
    final first = DateTime(now.year - 31, now.month, now.day);
    final last = DateTime(now.year - 15, now.month, now.day);
    var initial =
        DateTime.tryParse(_dob ?? '') ??
        DateTime(now.year - 20, now.month, now.day);
    if (initial.isBefore(first)) initial = first;
    if (initial.isAfter(last)) initial = last;

    final picked = await showDatePicker(
      context: context,
      initialDate: initial,
      firstDate: first,
      lastDate: last,
    );
    if (picked == null) return;
    setState(() => _dob = _fmt(picked));
  }

  Future<String?> _choose(String title, List<String> options, String? current) {
    final c = TkColors.of(context);
    return showModalBottomSheet<String>(
      context: context,
      backgroundColor: c.surface,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(22)),
      ),
      builder: (sheet) => SafeArea(
        child: ListView(
          shrinkWrap: true,
          padding: const EdgeInsets.all(12),
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 8, 12, 12),
              child: Text(title, style: tkText(sheet, size: 16, weight: 500)),
            ),
            for (final option in options)
              ListTile(
                title: Text(option, style: tkText(sheet, size: 14.5)),
                trailing: option == current
                    ? const Icon(Icons.check, color: TkColors.violet)
                    : null,
                onTap: () => Navigator.of(sheet).pop(option),
              ),
          ],
        ),
      ),
    );
  }

  Future<void> _save() async {
    setState(() {
      _saving = true;
      _errors = {};
      _flash = null;
      _formError = null;
    });
    try {
      await ApiClient.instance.updateProfile({
        'date_of_birth': _dob,
        'barangay': _barangay.text.trim(),
        'contact_number': _contact.text.trim(),
        'educational_attainment': _education,
        'employment_status': _employment,
        'livelihood_interests': _interests.text.trim(),
        'guardian_name': _guardianName.text.trim(),
        'guardian_relationship': _guardianRelationship.text.trim(),
        'guardian_contact': _guardianContact.text.trim(),
      });
      if (!mounted) return;
      setState(() => _flash = 'Your details were saved.');
      widget.onSaved();
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _errors = e.fieldErrors;
        _formError = e.fieldErrors.isEmpty ? e.message : null;
      });
    } catch (_) {
      if (!mounted) return;
      setState(
        () => _formError = 'Could not reach the server. Check the address and your connection.',
      );
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(
            child: CircularProgressIndicator(color: TkColors.violet),
          );
        }
        if (snapshot.hasError) {
          final message = snapshot.error is ApiException
              ? (snapshot.error as ApiException).message
              : 'Could not reach the server. Check the address and your connection.';
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: TkCard(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      message,
                      textAlign: TextAlign.center,
                      style: tkText(context, size: 14.5),
                    ),
                    TextButton(
                      onPressed: widget.onLogout,
                      child: const Text('Log out'),
                    ),
                  ],
                ),
              ),
            ),
          );
        }

        final data = snapshot.data!;
        final youth = data['youth'];
        if (youth is Map && !_filled) _fill(Map<String, dynamic>.from(youth));

        return ListView(
          padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
          children: [
            Center(
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 1024),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Padding(
                      padding: const EdgeInsets.only(top: 4, bottom: 18),
                      child: Text(
                        'Edit profile',
                        style: tkText(
                          context,
                          size: 27,
                          weight: 500,
                          spacing: -0.7,
                        ),
                      ),
                    ),
                    _accountCard(context, asMap(data['account'])),
                    const SizedBox(height: 16),
                    if (youth is Map)
                      _youthCard(context, asMap(data['options'])),
                    if (youth is Map) const SizedBox(height: 16),
                    _securityNote(context),
                    const SizedBox(height: 16),
                    _logoutCard(context),
                  ],
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _accountCard(BuildContext context, Map<String, dynamic> account) {
    final c = TkColors.of(context);
    return TkCard(
      child: Row(
        children: [
          TkAvatar(initials: tkInitials('${account['name'] ?? ''}'), size: 56),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${account['name'] ?? ''}',
                  style: tkText(context, size: 17, weight: 500),
                ),
                Text(
                  '${account['email'] ?? ''}',
                  style: tkText(context, size: 13, color: c.muted),
                ),
                const SizedBox(height: 8),
                TkChip(label: '${account['role_label'] ?? ''}'),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _securityNote(BuildContext context) {
    final c = TkColors.of(context);
    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Password and security',
            style: tkText(context, size: 16, weight: 500),
          ),
          const SizedBox(height: 6),
          Text(
            'Changing your password, two-factor authentication, and browser sessions are done on the Tuklas website.',
            style: tkText(context, size: 13, color: c.muted, height: 1.5),
          ),
        ],
      ),
    );
  }

  Widget _logoutCard(BuildContext context) {
    final c = TkColors.of(context);
    return TkCard(
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Sign out', style: tkText(context, size: 16, weight: 500)),
                const SizedBox(height: 4),
                Text(
                  'Sign out of your Tuklas account on this device.',
                  style: tkText(context, size: 13, color: c.muted, height: 1.4),
                ),
              ],
            ),
          ),
          const SizedBox(width: 12),
          FilledButton.icon(
            key: const ValueKey('profile-logout-button'),
            onPressed: widget.onLogout,
            icon: const Icon(Icons.logout, size: 18),
            label: const Text('Log out'),
            style: FilledButton.styleFrom(
              backgroundColor: c.badBg,
              foregroundColor: c.bad,
            ),
          ),
        ],
      ),
    );
  }

  Widget _label(BuildContext context, String text) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(text, style: tkText(context, size: 13, weight: 500)),
  );

  Widget _error(BuildContext context, String key) {
    final message = _errors[key];
    if (message == null) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 5),
      child: Text(
        message,
        style: tkText(context, size: 12.5, color: TkColors.of(context).bad),
      ),
    );
  }

  Widget _text(
    BuildContext context,
    String label,
    String key,
    TextEditingController controller, {
    TextInputType? type,
    String? hint,
    int lines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(context, label),
        TextField(
          controller: controller,
          keyboardType: type,
          minLines: lines,
          maxLines: lines,
          style: tkText(context, size: 14.5),
          decoration: InputDecoration(hintText: hint),
        ),
        _error(context, key),
      ],
    );
  }

  Widget _select(
    BuildContext context,
    String label,
    String key,
    String? value,
    String hint,
    VoidCallback onTap,
  ) {
    final c = TkColors.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _label(context, label),
        InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(13),
          child: Container(
            width: double.infinity,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 15),
            decoration: BoxDecoration(
              color: c.surface,
              borderRadius: BorderRadius.circular(13),
              border: Border.all(color: _errors[key] != null ? c.bad : c.line),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    value == null || value.isEmpty ? hint : value,
                    style: tkText(
                      context,
                      size: 14.5,
                      color: value == null || value.isEmpty ? c.muted : c.ink,
                    ),
                  ),
                ),
                Icon(Icons.expand_more, color: c.muted),
              ],
            ),
          ),
        ),
        _error(context, key),
      ],
    );
  }

  Widget _youthCard(BuildContext context, Map<String, dynamic> options) {
    final c = TkColors.of(context);
    final education = asList(options['education']).map((e) => '$e').toList();
    final employment = asList(options['employment']).map((e) => '$e').toList();

    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Youth details', style: tkText(context, size: 17, weight: 500)),
          const SizedBox(height: 6),
          Text(
            'Tell us about your schooling and interests. Tuklas uses these to suggest careers and TESDA Lingayen trainings. Suggestions are guidance, not guarantees of jobs, admission, or training slots.',
            style: tkText(context, size: 13, color: c.muted, height: 1.5),
          ),
          const SizedBox(height: 20),
          if (_flash != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: c.goodBg,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Text(
                _flash!,
                style: tkText(context, size: 13.5, color: c.good),
              ),
            ),
            const SizedBox(height: 16),
          ],
          if (_formError != null) ...[
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: c.badBg,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Text(
                _formError!,
                style: tkText(context, size: 13.5, color: c.bad),
              ),
            ),
            const SizedBox(height: 16),
          ],
          _select(
            context,
            'Date of birth',
            'date_of_birth',
            _dob,
            'Select your date of birth',
            _pickDob,
          ),
          Padding(
            padding: const EdgeInsets.only(top: 5),
            child: Text(
              'Tuklas is for youth aged 15 to 30.',
              style: tkText(context, size: 12.5, color: c.muted),
            ),
          ),
          const SizedBox(height: 16),
          _text(
            context,
            'Barangay',
            'barangay',
            _barangay,
            hint: 'Barangay in Bugallon',
          ),
          const SizedBox(height: 16),
          _text(
            context,
            'Contact number',
            'contact_number',
            _contact,
            type: TextInputType.phone,
          ),
          const SizedBox(height: 16),
          _select(
            context,
            'Educational attainment',
            'educational_attainment',
            _education,
            'Select one',
            () async {
              final picked = await _choose(
                'Educational attainment',
                education,
                _education,
              );
              if (picked != null) setState(() => _education = picked);
            },
          ),
          const SizedBox(height: 16),
          _select(
            context,
            'Current status',
            'employment_status',
            _employment,
            'Select one',
            () async {
              final picked = await _choose(
                'Current status',
                employment,
                _employment,
              );
              if (picked != null) setState(() => _employment = picked);
            },
          ),
          const SizedBox(height: 16),
          _text(
            context,
            'Interests and livelihood interests',
            'livelihood_interests',
            _interests,
            hint: 'For example: cooking, farming, electronics, selling online',
            lines: 3,
          ),
          if (_minor) ...[
            const SizedBox(height: 16),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: c.line),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Guardian (required under 18)',
                    style: tkText(context, size: 13.5, weight: 500),
                  ),
                  const SizedBox(height: 14),
                  _text(
                    context,
                    'Guardian name',
                    'guardian_name',
                    _guardianName,
                  ),
                  const SizedBox(height: 14),
                  _text(
                    context,
                    'Relationship',
                    'guardian_relationship',
                    _guardianRelationship,
                  ),
                  const SizedBox(height: 14),
                  _text(
                    context,
                    'Guardian contact number',
                    'guardian_contact',
                    _guardianContact,
                    type: TextInputType.phone,
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 22),
          Align(
            alignment: Alignment.centerRight,
            child: FilledButton(
              onPressed: _saving ? null : _save,
              style: FilledButton.styleFrom(
                backgroundColor: TkColors.violet,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(
                  horizontal: 24,
                  vertical: 14,
                ),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(13),
                ),
              ),
              child: _saving
                  ? const SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: Colors.white,
                      ),
                    )
                  : Text(
                      'Save details',
                      style: tkText(
                        context,
                        size: 14,
                        weight: 500,
                        color: Colors.white,
                      ),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}
