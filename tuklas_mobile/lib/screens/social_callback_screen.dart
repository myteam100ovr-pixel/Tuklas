import 'package:flutter/material.dart';

import '../api.dart';
import 'home_shell.dart';
import 'login_screen.dart';

class SocialCallbackScreen extends StatefulWidget {
  const SocialCallbackScreen({super.key, required this.uri});
  final Uri uri;
  @override
  State<SocialCallbackScreen> createState() => _SocialCallbackScreenState();
}

class _SocialCallbackScreenState extends State<SocialCallbackScreen> {
  final _code = TextEditingController();
  bool _busy = false, _needsCode = false;
  String? _error;
  @override
  void initState() {
    super.initState();
    _error = widget.uri.queryParameters['error'];
    if (_error == null) _exchange();
  }

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _exchange() async {
    final ticket = widget.uri.queryParameters['ticket'];
    if (ticket == null || ticket.isEmpty) {
      setState(
        () => _error = 'This sign-in link is invalid. Please sign in again.',
      );
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ApiClient.instance.exchangeSocialTicket(
        ticket,
        code: _code.text.trim(),
      );
      if (!mounted) return;
      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (_) => const HomeShell()),
        (_) => false,
      );
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e is ApiException
              ? e.message
              : 'Could not finish sign-in. Try again.';
          _needsCode = e is ApiException && e.twoFactorRequired || _needsCode;
        });
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Finish sign-in')),
    body: ListView(
      padding: const EdgeInsets.all(24),
      children: [
        if (_busy) const LinearProgressIndicator(),
        if (_error != null) Text(_error!),
        if (_needsCode)
          TextField(
            controller: _code,
            decoration: const InputDecoration(
              labelText: 'Authenticator or recovery code',
            ),
          ),
        if (!_busy && widget.uri.queryParameters['ticket'] != null)
          FilledButton(onPressed: _exchange, child: const Text('Continue')),
        TextButton(
          onPressed: _busy
              ? null
              : () => Navigator.pushAndRemoveUntil(
                  context,
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                  (_) => false,
                ),
          child: const Text('Back to sign-in'),
        ),
      ],
    ),
  );
}
