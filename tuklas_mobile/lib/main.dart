import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'api.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const TuklasApp());
  unawaited(themeController.load());
}

class TuklasApp extends StatelessWidget {
  const TuklasApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: themeController,
      builder: (context, _) => MaterialApp(
        title: 'Tuklas',
        debugShowCheckedModeBanner: false,
        theme: tkTheme(Brightness.light),
        darkTheme: tkTheme(Brightness.dark),
        themeMode: themeController.mode,
        home: const Gate(),
      ),
    );
  }
}

/// Opens the app if a saved sign-in still works, otherwise the login screen.
class Gate extends StatefulWidget {
  const Gate({super.key, this.checkSignedIn});

  @visibleForTesting
  final Future<bool> Function()? checkSignedIn;

  @override
  State<Gate> createState() => _GateState();
}

class _GateState extends State<Gate> {
  _StartupStatus _status = _StartupStatus.checking;

  @override
  void initState() {
    super.initState();
    unawaited(_checkSession());
  }

  Future<_StartupStatus> _resolveSignedIn() async {
    final signedInCheck = widget.checkSignedIn;
    final result = signedInCheck == null
        ? await _check().timeout(
            const Duration(seconds: 15),
            onTimeout: () => _StartupStatus.unavailable,
          )
        : await signedInCheck()
              .then(
                (signedIn) => signedIn
                    ? _StartupStatus.signedIn
                    : _StartupStatus.signedOut,
              )
              .timeout(
                const Duration(seconds: 15),
                onTimeout: () => _StartupStatus.unavailable,
              );
    return result;
  }

  Future<void> _checkSession() async {
    try {
      final status = await _resolveSignedIn();
      if (mounted) {
        setState(() => _status = status);
      }
    } catch (error, stackTrace) {
      FlutterError.reportError(
        FlutterErrorDetails(exception: error, stack: stackTrace),
      );
      if (mounted) {
        setState(() => _status = _StartupStatus.unavailable);
      }
    }
  }

  Future<_StartupStatus> _check() async {
    if (await ApiClient.instance.token == null) {
      return _StartupStatus.signedOut;
    }

    try {
      await ApiClient.instance.me();
      return _StartupStatus.signedIn;
    } on ApiException catch (error) {
      return error.statusCode == 401
          ? _StartupStatus.signedOut
          : _StartupStatus.unavailable;
    } on SocketException {
      return _StartupStatus.unavailable;
    } on TimeoutException {
      return _StartupStatus.unavailable;
    } on http.ClientException {
      return _StartupStatus.unavailable;
    }
  }

  void _retry() {
    setState(() {
      _status = _StartupStatus.checking;
    });
    unawaited(_checkSession());
  }

  @override
  Widget build(BuildContext context) {
    return switch (_status) {
      _StartupStatus.checking => const _StartupSplash(),
      _StartupStatus.unavailable => _ServerUnavailable(onRetry: _retry),
      _StartupStatus.signedIn => const HomeShell(),
      _StartupStatus.signedOut => const LoginScreen(),
    };
  }
}

enum _StartupStatus { checking, signedIn, signedOut, unavailable }

class _ServerUnavailable extends StatelessWidget {
  const _ServerUnavailable({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);

    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'tuklas',
                    style: tkText(
                      context,
                      size: 40,
                      weight: 500,
                      spacing: -2,
                      height: 1,
                    ),
                  ),
                  const SizedBox(height: 24),
                  Icon(Icons.cloud_off_outlined, size: 36, color: colors.muted),
                  const SizedBox(height: 14),
                  Text(
                    'Can’t reach the Tuklas server',
                    textAlign: TextAlign.center,
                    style: tkText(context, size: 18, weight: 500),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    'Your saved sign-in is still on this device. In VS Code, launch Tuklas Mobile to check the database and reconnect the Android API, then retry. For a phone without USB debugging, use your computer’s LAN IP and start Laravel with --host=0.0.0.0.',
                    textAlign: TextAlign.center,
                    style: tkText(
                      context,
                      size: 13,
                      color: colors.muted,
                      height: 1.5,
                    ),
                  ),
                  const SizedBox(height: 18),
                  FilledButton.icon(
                    onPressed: onRetry,
                    icon: const Icon(Icons.refresh),
                    label: const Text('Retry connection'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _StartupSplash extends StatelessWidget {
  const _StartupSplash();

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                'tuklas',
                style: tkText(
                  context,
                  size: 40,
                  weight: 500,
                  spacing: -2,
                  height: 1,
                ),
              ),
              const SizedBox(height: 28),
              const SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  strokeWidth: 2.5,
                  color: TkColors.violet,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
