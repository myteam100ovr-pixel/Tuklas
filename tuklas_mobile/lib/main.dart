import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart' show defaultTargetPlatform;
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import 'api.dart';
import 'app_update.dart';
import 'firebase_options.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'screens/welcome_screen.dart';
import 'theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  if (defaultTargetPlatform == TargetPlatform.android ||
      defaultTargetPlatform == TargetPlatform.iOS) {
    try {
      await Firebase.initializeApp(
        options: DefaultFirebaseOptions.currentPlatform,
      );
    } catch (error, stackTrace) {
      FlutterError.reportError(
        FlutterErrorDetails(exception: error, stack: stackTrace),
      );
    }
  }
  runApp(const TuklasApp());
  unawaited(themeController.load());
  unawaited(motionController.load());
}

class TuklasApp extends StatelessWidget {
  const TuklasApp({super.key});

  @override
  Widget build(BuildContext context) {
    return ListenableBuilder(
      listenable: Listenable.merge([themeController, motionController]),
      builder: (context, _) => MaterialApp(
        title: 'Tuklas',
        debugShowCheckedModeBanner: false,
        theme: tkTheme(
          Brightness.light,
          reduceMotion: motionController.enabled,
        ),
        darkTheme: tkTheme(
          Brightness.dark,
          reduceMotion: motionController.enabled,
        ),
        themeMode: themeController.mode,
        builder: (context, child) => MediaQuery(
          data: MediaQuery.of(context).copyWith(
            disableAnimations:
                motionController.enabled ||
                MediaQuery.of(context).disableAnimations,
          ),
          child: child ?? const SizedBox.shrink(),
        ),
        home: const AppUpdateWatcher(child: FirstLaunch(child: Gate())),
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

class _GateState extends State<Gate> with WidgetsBindingObserver {
  _StartupStatus _status = _StartupStatus.checking;
  Timer? _retryTimer;
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    unawaited(_checkSession());
  }

  @override
  void dispose() {
    _retryTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed &&
        (_status == _StartupStatus.unavailable ||
            _status == _StartupStatus.serverResponded)) {
      unawaited(_checkSession());
    }
  }

  Future<_StartupStatus> _resolveSignedIn() async {
    final signedInCheck = widget.checkSignedIn;
    final result = signedInCheck == null
        ? await _check().timeout(
            const Duration(seconds: 35),
            onTimeout: () => _StartupStatus.unavailable,
          )
        : await signedInCheck()
              .then(
                (signedIn) => signedIn
                    ? _StartupStatus.signedIn
                    : _StartupStatus.signedOut,
              )
              .timeout(
                const Duration(seconds: 35),
                onTimeout: () => _StartupStatus.unavailable,
              );
    return result;
  }

  Future<void> _checkSession() async {
    if (_checking) return;
    _checking = true;
    _retryTimer?.cancel();
    try {
      final status = await _resolveSignedIn();
      if (mounted) {
        setState(() => _status = status);
        if (status == _StartupStatus.unavailable ||
            status == _StartupStatus.serverResponded) {
          _retryTimer = Timer(
            const Duration(seconds: 12),
            () => unawaited(_checkSession()),
          );
        }
      }
    } catch (error, stackTrace) {
      FlutterError.reportError(
        FlutterErrorDetails(exception: error, stack: stackTrace),
      );
      if (mounted) {
        setState(() => _status = _StartupStatus.unavailable);
        _retryTimer = Timer(
          const Duration(seconds: 12),
          () => unawaited(_checkSession()),
        );
      }
    } finally {
      _checking = false;
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
          : error.statusCode == null
          ? _StartupStatus.unavailable
          : _StartupStatus.serverResponded;
    } on SocketException {
      return _StartupStatus.unavailable;
    } on TimeoutException {
      return _StartupStatus.unavailable;
    } on http.ClientException {
      return _StartupStatus.unavailable;
    } on HandshakeException {
      return _StartupStatus.unavailable;
    }
  }

  void _retry() {
    if (_checking) return;
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
      _StartupStatus.serverResponded => _ServerUnavailable(
        onRetry: _retry,
        serverResponded: true,
      ),
      _StartupStatus.signedIn => const HomeShell(),
      _StartupStatus.signedOut => const LoginScreen(),
    };
  }
}

enum _StartupStatus {
  checking,
  signedIn,
  signedOut,
  unavailable,
  serverResponded,
}

class _ServerUnavailable extends StatelessWidget {
  const _ServerUnavailable({
    required this.onRetry,
    this.serverResponded = false,
  });

  final VoidCallback onRetry;
  final bool serverResponded;

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
                    serverResponded
                        ? 'Tuklas is online, but sign-in could not be checked'
                        : 'Can’t reach the Tuklas server',
                    textAlign: TextAlign.center,
                    style: tkText(context, size: 18, weight: 500),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    serverResponded
                        ? 'Your internet connection is working. The server returned an error while checking your saved sign-in. Please retry in a moment.'
                        : 'Your saved sign-in is still on this device. Tuklas could not connect to its server. It will retry automatically; you can also retry now.',
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
