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
  static const _barangaysByMunicipality = <String, List<String>>{
    'Bugallon': [
      'Angarian',
      'Asinan',
      'Banaga',
      'Bacabac',
      'Bolaoen',
      'Buenlag',
      'Cabayaoasan',
      'Cayanga',
      'Gueset',
      'Hacienda',
      'Laguit Centro',
      'Laguit Padilla',
      'Magtaking',
      'Pangascasan',
      'Pantal',
      'Poblacion',
      'Polong',
      'Portic',
      'Salasa',
      'Salomague Norte',
      'Salomague Sur',
      'Samat',
      'San Francisco',
      'Umanday',
    ],
    'Lingayen': [
      'Aliwekwek',
      'Baay',
      'Balangobong',
      'Balococ',
      'Bantayan',
      'Basing',
      'Capandanan',
      'Domalandan Center',
      'Domalandan East',
      'Domalandan West',
      'Dorongan',
      'Dulag',
      'Estanza',
      'Lasip',
      'Libsong East',
      'Libsong West',
      'Malawa',
      'Malimpuec',
      'Maniboc',
      'Matalava',
      'Naguelguel',
      'Namolan',
      'Pangapisan North',
      'Pangapisan Sur',
      'Poblacion',
      'Quibaol',
      'Rosario',
      'Sabangan',
      'Talogtog',
      'Tonton',
      'Tumbar',
      'Wawa',
    ],
  };

  final _email = TextEditingController();
  final _name = TextEditingController();
  final _lastName = TextEditingController();
  final _middleInitial = TextEditingController();
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
  bool _showPasswordConfirmation = false;
  String? _error;
  String? _notice;
  String? _registrationMunicipality;
  String? _registrationBarangay;

  @override
  void dispose() {
    _email.dispose();
    _name.dispose();
    _lastName.dispose();
    _middleInitial.dispose();
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
    if (_registering && _passwordStrength(_password.text) == 0) {
      setState(
        () => _error = 'Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.',
      );
      return;
    }
    if (_registering && _password.text != _passwordConfirmation.text) {
      setState(() => _error = 'Passwords do not match.');
      return;
    }
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
    if (_registering &&
        (_registrationMunicipality == null || _registrationBarangay == null)) {
      setState(() => _error = 'Choose your municipality and barangay.');
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
            'middle_initial': _middleInitial.text.trim(),
            'last_name': _lastName.text.trim(),
            'date_of_birth': _birthDate.text.trim(),
            'contact_number': _contact.text.trim(),
            'barangay':
                '${_registrationBarangay!}, ${_registrationMunicipality!}',
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

  int _passwordStrength(String value) {
    if (value.length < 12 ||
        value.length > 72 ||
        !RegExp(r'[a-z]').hasMatch(value) ||
        !RegExp(r'[A-Z]').hasMatch(value) ||
        !RegExp(r'[0-9]').hasMatch(value) ||
        !RegExp(r'[^a-zA-Z0-9]').hasMatch(value)) {
      return 0;
    }

    return value.length >= 16 ? 2 : 1;
  }

  Widget _passwordStrengthFeedback(BuildContext context) {
    final strength = _passwordStrength(_password.text);
    const labels = ['Weak', 'Okay', 'Strong'];
    const messages = [
      'Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.',
      'Okay. It meets the password requirements.',
      'Strong. This password meets the requirements with extra length.',
    ];
    final colors = [
      Theme.of(context).colorScheme.error,
      Colors.orange.shade800,
      Colors.green.shade700,
    ];

    return Semantics(
      liveRegion: true,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Password strength'),
              Text(
                labels[strength],
                style: TextStyle(
                  color: colors[strength],
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 5),
          LinearProgressIndicator(
            value: (strength + 1) / 3,
            color: colors[strength],
          ),
          const SizedBox(height: 5),
          Text(messages[strength]),
        ],
      ),
    );
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
                              _label(context, 'Last name'),
                              TextField(
                                controller: _lastName,
                                textCapitalization: TextCapitalization.words,
                                autofillHints: const [AutofillHints.familyName],
                                style: tkText(context, size: 14.5),
                              ),
                              const SizedBox(height: 16),
                              _label(context, 'First name'),
                              TextField(
                                controller: _name,
                                textCapitalization: TextCapitalization.words,
                                autofillHints: const [AutofillHints.givenName],
                                style: tkText(context, size: 14.5),
                              ),
                              const SizedBox(height: 16),
                              _label(context, 'Middle initial (optional)'),
                              TextField(
                                controller: _middleInitial,
                                textCapitalization:
                                    TextCapitalization.characters,
                                autofillHints: const [AutofillHints.middleName],
                                style: tkText(context, size: 14.5),
                              ),
                              const SizedBox(height: 16),
                              _label(context, 'Contact number'),
                              TextField(
                                controller: _contact,
                                keyboardType: TextInputType.phone,
                              ),
                              const SizedBox(height: 16),
                              _label(context, 'Municipality'),
                              DropdownButtonFormField<String>(
                                initialValue: _registrationMunicipality,
                                isExpanded: true,
                                hint: const Text('Choose municipality'),
                                items: [
                                  for (final municipality
                                      in _barangaysByMunicipality.keys)
                                    DropdownMenuItem(
                                      value: municipality,
                                      child: Text(municipality),
                                    ),
                                ],
                                onChanged: (municipality) => setState(() {
                                  _registrationMunicipality = municipality;
                                  _registrationBarangay = null;
                                  _barangay.clear();
                                }),
                              ),
                              const SizedBox(height: 16),
                              _label(context, 'Barangay'),
                              DropdownButtonFormField<String>(
                                key: ValueKey(_registrationMunicipality),
                                initialValue: _registrationBarangay,
                                isExpanded: true,
                                hint: Text(
                                  _registrationMunicipality == null
                                      ? 'Choose municipality first'
                                      : 'Choose barangay',
                                ),
                                items: [
                                  for (final barangay
                                      in _barangaysByMunicipality[_registrationMunicipality] ??
                                          const <String>[])
                                    DropdownMenuItem(
                                      value: barangay,
                                      child: Text(barangay),
                                    ),
                                ],
                                onChanged: _registrationMunicipality == null
                                    ? null
                                    : (barangay) => setState(() {
                                        _registrationBarangay = barangay;
                                        _barangay.text = barangay == null
                                            ? ''
                                            : '$barangay, $_registrationMunicipality';
                                      }),
                              ),
                              const SizedBox(height: 16),
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
                              onChanged: _registering
                                  ? (_) => setState(() {})
                                  : null,
                              decoration: InputDecoration(
                                hintText: _registering
                                    ? 'Use 1 special character, e.g. @'
                                    : null,
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
                              const SizedBox(height: 8),
                              _passwordStrengthFeedback(context),
                            ],
                            if (_registering) ...[
                              const SizedBox(height: 16),
                              _label(context, 'Confirm password'),
                              TextField(
                                controller: _passwordConfirmation,
                                obscureText: !_showPasswordConfirmation,
                                onChanged: (_) => setState(() {}),
                                decoration: InputDecoration(
                                  suffixIcon: IconButton(
                                    tooltip: _showPasswordConfirmation
                                        ? 'Hide password'
                                        : 'Show password',
                                    onPressed: () => setState(
                                      () => _showPasswordConfirmation =
                                          !_showPasswordConfirmation,
                                    ),
                                    icon: Icon(
                                      _showPasswordConfirmation
                                          ? Icons.visibility_off_outlined
                                          : Icons.visibility_outlined,
                                    ),
                                  ),
                                ),
                                autofillHints: const [
                                  AutofillHints.newPassword,
                                ],
                                style: tkText(context, size: 14.5),
                              ),
                              if (_passwordConfirmation.text.isNotEmpty) ...[
                                const SizedBox(height: 6),
                                Text(
                                  _passwordConfirmation.text == _password.text
                                      ? 'Passwords match.'
                                      : 'Passwords do not match.',
                                  style: TextStyle(
                                    color:
                                        _passwordConfirmation.text ==
                                            _password.text
                                        ? Colors.green.shade700
                                        : Theme.of(context).colorScheme.error,
                                  ),
                                ),
                              ],
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
