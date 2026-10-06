import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';
import 'home_shell.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _name = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirmation = TextEditingController();
  final _code = TextEditingController();
  bool _loading = false;
  bool _needsCode = false;
  bool _registering = false;
  String? _error;
  String? _notice;

  @override
  void dispose() {
    _email.dispose();
    _name.dispose();
    _password.dispose();
    _passwordConfirmation.dispose();
    _code.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      if (_registering) {
        final response = await ApiClient.instance.register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          password: _password.text,
          passwordConfirmation: _passwordConfirmation.text,
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
      _error = null;
      _notice = null;
    });
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
                padding: const EdgeInsets.fromLTRB(16, 72, 16, 32),
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
                        padding: const EdgeInsets.all(26),
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
                              _label(context, 'Full name'),
                              TextField(
                                controller: _name,
                                textCapitalization: TextCapitalization.words,
                                autofillHints: const [AutofillHints.name],
                                style: tkText(context, size: 14.5),
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
                              obscureText: true,
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
                              _label(context, 'Two-factor code'),
                              TextField(
                                controller: _code,
                                keyboardType: TextInputType.number,
                                style: tkText(context, size: 14.5),
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
