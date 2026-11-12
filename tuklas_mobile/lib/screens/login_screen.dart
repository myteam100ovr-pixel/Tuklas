import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../config.dart';

import '../api.dart';
import '../date_format.dart';
import '../theme.dart';
import '../ui.dart';
import 'home_shell.dart';
import 'workspace_form.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _name = TextEditingController();
  final _lastName = TextEditingController();
  final _birthDate = TextEditingController();
  final _contact = TextEditingController();
  final _barangay = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirmation = TextEditingController();
  final _code = TextEditingController();
  bool _loading = false;
  bool _needsCode = false;
  bool _registering = false;
  bool _acceptedTerms = false;
  bool _showPassword = false;
  String? _error;
  String? _notice;

  @override
  void dispose() {
    _email.dispose();
    _name.dispose();
    _lastName.dispose();
    _birthDate.dispose();
    _contact.dispose();
    _barangay.dispose();
    _password.dispose();
    _passwordConfirmation.dispose();
    _code.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_loading) return;
    if (_registering && !_acceptedTerms) {
      setState(
        () => _error = 'Please accept the Terms of use and Privacy notice.',
      );
      return;
    }
    if (_registering && _birthDate.text.trim().isEmpty) {
      setState(() => _error = 'Select your date of birth.');
      return;
    }
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      if (_registering) {
        final response = await ApiClient.instance.workspace(
          '/auth/register',
          method: 'POST',
          auth: false,
          data: {
            'first_name': _name.text.trim(),
            'last_name': _lastName.text.trim(),
            'date_of_birth': _birthDate.text.trim(),
            'contact_number': _contact.text.trim(),
            'barangay': _barangay.text.trim(),
            'email': _email.text.trim(),
            'password': _password.text,
            'password_confirmation': _passwordConfirmation.text,
            'device_name': 'tuklas-mobile',
            'terms': _acceptedTerms,
          },
        );
        if (!mounted) return;
        setState(() {
          _registering = false;
          _password.clear();
          _passwordConfirmation.clear();
          _notice =
              response['message'] as String? ??
              'Account created. Verify your email address before signing in.';
        });
      } else {
        await ApiClient.instance.login(
          _email.text.trim(),
          _password.text,
          code: _code.text.trim(),
        );
        if (!mounted) return;
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const HomeShell()),
          (_) => false,
        );
      }
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _needsCode = _needsCode || e.twoFactorRequired;
      });
    } catch (_) {
      if (!mounted) return;
      setState(
        () => _error = 'Could not reach the server. Check the address and your connection.',
      );
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _setRegistering(bool registering) {
    setState(() {
      _registering = registering;
      _needsCode = false;
      _acceptedTerms = false;
      _code.clear();
      _password.clear();
      _passwordConfirmation.clear();
      _error = null;
      _notice = null;
    });
  }

  Future<void> _pickBirthDate() async {
    final now = DateTime.now();
    final firstDate = DateTime(
      now.year - 31,
      now.month,
      now.day,
    ).add(const Duration(days: 1));
    final lastDate = DateTime(now.year - 15, now.month, now.day);
    final initialDate = DateTime(now.year - 20, now.month, now.day);
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: firstDate,
      lastDate: lastDate,
      helpText: 'Choose a date between ages 15 and 30',
    );

    if (picked != null) {
      setState(() => _birthDate.text = formatDateForApi(picked));
    }
  }

  Widget _label(BuildContext context, String text) => Padding(
    padding: const EdgeInsets.only(bottom: 6),
    child: Text(text, style: tkText(context, size: 13, weight: 500)),
  );

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);

    return Scaffold(
      body: SafeArea(
        child: Stack(
          children: [
            Center(
              child: SingleChildScrollView(
                padding: EdgeInsets.fromLTRB(
                  16,
                  MediaQuery.sizeOf(context).height < 700 ? 24 : 48,
                  16,
                  32,
                ),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 432),
                  child: Column(
                    children: [
                      Text(
                        'tuklas',
                        style: tkText(
                          context,
                          size: 30,
                          weight: 500,
                          spacing: -1.5,
                          height: 1,
                        ),
                      ),
                      const SizedBox(height: 22),
                      TkCard(
                        padding: EdgeInsets.all(
                          MediaQuery.sizeOf(context).width < 380 ? 18 : 26,
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            if (_error != null) ...[
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: c.badBg,
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: Text(
                                  _error!,
                                  style: tkText(
                                    context,
                                    size: 13.5,
                                    color: c.bad,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 16),
                            ],
                            if (_notice != null) ...[
                              Container(
                                width: double.infinity,
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: c.goodBg,
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                child: Text(
                                  _notice!,
                                  style: tkText(
                                    context,
                                    size: 13.5,
                                    color: c.good,
                                  ),
                                ),
                              ),
                              const SizedBox(height: 16),
                            ],
                            if (_registering) ...[
                              _label(context, 'First name'),
                              TextField(
                                controller: _name,
                                textCapitalization: TextCapitalization.words,
                                autofillHints: const [AutofillHints.name],
                                style: tkText(context, size: 14.5),
                              ),
                              const SizedBox(height: 16),
                              for (final field in [
                                ('Last name', _lastName),
                                ('Contact number', _contact),
                                ('Barangay', _barangay),
                              ]) ...[
                                _label(context, field.$1),
                                TextField(controller: field.$2),
                                const SizedBox(height: 16),
                              ],
                              _label(context, 'Date of birth'),
                              InkWell(
                                onTap: _pickBirthDate,
                                borderRadius: BorderRadius.circular(13),
                                child: InputDecorator(
                                  decoration: const InputDecoration(
                                    hintText: 'Select your date of birth',
                                    suffixIcon: Icon(
                                      Icons.calendar_month_outlined,
                                    ),
                                  ),
                                  child: Text(
                                    _birthDate.text.isEmpty
                                        ? 'Select your date of birth'
                                        : formatTuklasDate(_birthDate.text),
                                    style: tkText(
                                      context,
                                      size: 14.5,
                                      color: _birthDate.text.isEmpty
                                          ? c.muted
                                          : c.ink,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(height: 16),
                            ],
                            _label(context, 'Email'),
                            TextField(
                              controller: _email,
                              keyboardType: TextInputType.emailAddress,
                              autofillHints: const [AutofillHints.email],
                              style: tkText(context, size: 14.5),
                            ),
                            const SizedBox(height: 16),
                            _label(context, 'Password'),
                            TextField(
                              controller: _password,
                              obscureText: !_showPassword,
                              enableSuggestions: false,
                              autocorrect: false,
                              decoration: InputDecoration(
                                suffixIcon: IconButton(
                                  tooltip: _showPassword
                                      ? 'Hide password'
                                      : 'Show password',
                                  onPressed: () => setState(
                                    () => _showPassword = !_showPassword,
                                  ),
                                  icon: Icon(
                                    _showPassword
                                        ? Icons.visibility_off_outlined
                                        : Icons.visibility_outlined,
                                  ),
                                ),
                              ),
                              autofillHints: const [AutofillHints.password],
                              style: tkText(context, size: 14.5),
                            ),
                            if (_registering) ...[
                              const SizedBox(height: 16),
                              _label(context, 'Confirm password'),
                              TextField(
                                controller: _passwordConfirmation,
                                obscureText: true,
                                autofillHints: const [
                                  AutofillHints.newPassword,
                                ],
                                style: tkText(context, size: 14.5),
                              ),
                            ],
                            if (_needsCode) ...[
                              const SizedBox(height: 16),
                              _label(context, 'Authenticator or recovery code'),
                              TextField(
                                controller: _code,
                                keyboardType: TextInputType.text,
                                style: tkText(context, size: 14.5),
                              ),
                            ],
                            if (_registering) ...[
                              Material(
                                color: Colors.transparent,
                                child: CheckboxListTile(
                                  contentPadding: EdgeInsets.zero,
                                  controlAffinity:
                                      ListTileControlAffinity.leading,
                                  value: _acceptedTerms,
                                  onChanged: _loading
                                      ? null
                                      : (value) => setState(
                                          () => _acceptedTerms = value ?? false,
                                        ),
                                  title: const Text(
                                    'I agree to the Terms of use and acknowledge the Privacy notice.',
                                  ),
                                ),
                              ),
                              Wrap(
                                children: [
                                  for (final policy in [
                                    ('Terms of use', '/terms-of-service'),
                                    ('Privacy notice', '/privacy-policy'),
                                  ])
                                    TextButton(
                                      onPressed: () async {
                                        try {
                                          final opened = await launchUrl(
                                            Uri.parse(AppConfig.apiBase)
                                                .resolve(policy.$2),
                                            mode:
                                                LaunchMode.externalApplication,
                                          );
                                          if (!opened) {
                                            throw StateError(
                                              'Could not open page',
                                            );
                                          }
                                        } catch (_) {
                                          if (mounted) {
                                            setState(
                                              () => _error = 'Could not open this page. Please try again.',
                                            );
                                          }
                                        }
                                      },
                                      child: Text(policy.$1),
                                    ),
                                ],
                              ),
                            ],
                            const SizedBox(height: 20),
                            Align(
                              alignment: Alignment.centerRight,
                              child: FilledButton(
                                onPressed: _loading ? null : _submit,
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
                                child: _loading
                                    ? const SizedBox(
                                        width: 18,
                                        height: 18,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 2,
                                          color: Colors.white,
                                        ),
                                      )
                                    : Text(
                                        _registering
                                            ? 'Create account'
                                            : 'Log in',
                                        style: tkText(
                                          context,
                                          size: 14,
                                          weight: 500,
                                          color: Colors.white,
                                        ),
                                      ),
                              ),
                            ),
                            if (!_registering)
                              Padding(
                                padding: const EdgeInsets.only(top: 16),
                                child: Text(
                                  'Previously used Google or Facebook? Reset your password using the same email.',
                                  style: tkText(
                                    context,
                                    size: 13,
                                    color: c.muted,
                                  ),
                                ),
                              ),
                            if (!_registering)
                              TextButton(
                                onPressed: _loading
                                    ? null
                                    : () async {
                                        final result = await openWorkspaceForm(
                                          context,
                                          title: 'Request password reset',
                                          path: '/auth/forgot-password',
                                          auth: false,
                                          fields: const [
                                            WorkspaceField(
                                              'email',
                                              'Email',
                                              required: true,
                                            ),
                                          ],
                                          initial: {'email': _email.text},
                                        );
                                        if (mounted && result != null) {
                                          setState(
                                            () => _notice =
                                                '${result['message']}',
                                          );
                                        }
                                      },
                                child: const Text('Forgot password?'),
                              ),
                            const SizedBox(height: 10),
                            Center(
                              child: TextButton(
                                onPressed: _loading
                                    ? null
                                    : () => _setRegistering(!_registering),
                                child: Text(
                                  _registering
                                      ? 'Already have an account? Log in'
                                      : 'New to Tuklas? Create an account',
                                  style: tkText(
                                    context,
                                    size: 13,
                                    color: TkColors.violet,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Positioned(
              top: 12,
              right: 16,
              child: TkIconButton(
                icon: c.isDark
                    ? Icons.dark_mode_outlined
                    : Icons.light_mode_outlined,
                tooltip: 'Light / dark mode',
                onTap: () => themeController.toggle(context),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
