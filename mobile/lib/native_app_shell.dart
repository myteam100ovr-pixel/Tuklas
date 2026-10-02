import 'dart:async';

import 'package:app_links/app_links.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart' show MissingPluginException;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import 'auth_screens.dart';
import 'dashboard_screen.dart';
import 'tuklas_api.dart';
import 'tuklas_theme.dart';
import 'workflow_screens.dart';

class TuklasApp extends StatefulWidget {
  const TuklasApp({super.key});

  @override
  State<TuklasApp> createState() => _TuklasAppState();
}

class _TuklasAppState extends State<TuklasApp> {
  final _api = TuklasApi();
  final _links = AppLinks();
  final _navigatorKey = GlobalKey<NavigatorState>();
  TuklasThemeController? _theme;
  StreamSubscription<Uri>? _linkSubscription;
  Map<String, dynamic>? _user;
  String? _socialTicket;
  String? _socialError;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    unawaited(_initialize());
  }

  Future<void> _initialize() async {
    _theme = TuklasThemeController(await SharedPreferences.getInstance());
    try {
      await _api.initialize();
    } catch (error) {
      _socialError = TuklasApi.errorMessage(error);
    }
    try {
      _linkSubscription = _links.uriLinkStream.listen(_handleLink);
      unawaited(_restoreInitialLink());
    } on MissingPluginException {
      _linkSubscription?.cancel();
      _linkSubscription = null;
    } catch (_) {
      _linkSubscription?.cancel();
      _linkSubscription = null;
    }
    if (_api.isAuthenticated) {
      try {
        _user = await _api.currentUser().timeout(const Duration(seconds: 8));
      } catch (_) {
        try {
          await _api.logout();
        } catch (_) {
          _user = null;
        }
      }
    }
    if (mounted) setState(() => _loading = false);
  }

  Future<void> _restoreInitialLink() async {
    try {
      final initialLink = await _links.getInitialLink();
      if (initialLink != null) await _handleLink(initialLink, notify: true);
    } catch (_) {
      // The initial deep link is optional; the app remains usable without it.
    }
  }

  @override
  void dispose() {
    _linkSubscription?.cancel();
    _theme?.dispose();
    super.dispose();
  }

  Future<void> _handleLink(Uri uri, {bool notify = true}) async {
    if (uri.scheme != 'tuklas' || uri.host != 'auth' || uri.path != '/social') return;
    final error = uri.queryParameters['error'];
    if (error != null) {
      _socialError = error;
      _socialTicket = null;
      if (notify && mounted) {
        setState(() {});
        WidgetsBinding.instance.addPostFrameCallback((_) {
          final context = _navigatorKey.currentContext;
          if (context != null) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
        });
      }
      return;
    }

    final ticket = uri.queryParameters['ticket'];
    if (ticket == null || ticket.isEmpty) return;
    try {
      final result = await _api.exchangeSocialTicket(ticket: ticket, deviceName: 'Tuklas mobile');
      _user = Map<String, dynamic>.from(result['user'] as Map);
      _socialTicket = null;
      _socialError = null;
    } catch (error) {
      if (TuklasApi.needsTwoFactor(error)) {
        _socialTicket = ticket;
      } else {
        _socialTicket = null;
        _socialError = TuklasApi.errorMessage(error);
      }
    }
    if (notify && mounted) setState(() {});
  }

  void _authenticated(Map<String, dynamic> user) {
    setState(() {
      _user = user;
      _socialTicket = null;
      _socialError = null;
    });
  }

  Future<void> _signOut() async {
    await _api.logout();
    if (mounted) setState(() {
      _user = null;
      _socialTicket = null;
    });
  }

  Future<void> _openServerSettings() async {
    final context = _navigatorKey.currentContext;
    if (context == null) return;
    final controller = TextEditingController(text: _api.siteBaseUrl);
    final address = await showDialog<String>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Tuklas server'),
        content: TextField(
          controller: controller,
          keyboardType: TextInputType.url,
          autocorrect: false,
          decoration: const InputDecoration(labelText: 'Server address', hintText: 'https://tuklas.example.com'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(dialogContext), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(dialogContext, controller.text.trim()), child: const Text('Save')),
        ],
      ),
    );
    controller.dispose();
    if (address == null || address.isEmpty) return;
    try {
      await _api.setServerAddress(address);
      await _signOut();
      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Server saved. Sign in to continue.')));
    } catch (error) {
      if (context.mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(TuklasApi.errorMessage(error))));
    }
  }

  void _openChat() {
    final context = _navigatorKey.currentContext;
    if (context == null) return;
    Navigator.of(context).push(MaterialPageRoute<void>(builder: (_) => TuklasChatScreen(api: _api)));
  }

  @override
  Widget build(BuildContext context) {
    final theme = _theme;
    if (_loading || theme == null) {
      return MaterialApp(
        debugShowCheckedModeBanner: false,
        theme: TuklasTheme.light,
        home: const Scaffold(body: Center(child: CircularProgressIndicator())),
      );
    }

    return AnimatedBuilder(
      animation: theme,
      builder: (context, _) => MaterialApp(
        navigatorKey: _navigatorKey,
        title: 'Tuklas',
        debugShowCheckedModeBanner: false,
        theme: TuklasTheme.light,
        darkTheme: TuklasTheme.dark,
        themeMode: theme.mode,
        home: _socialTicket != null
            ? TuklasSocialChallengeScreen(
                api: _api,
                ticket: _socialTicket!,
                onAuthenticated: _authenticated,
                onBack: () => setState(() => _socialTicket = null),
              )
            : _user == null
                ? _GuestShell(
                    api: _api,
                    theme: theme,
                    initialSocialError: _socialError,
                    onAuthenticated: _authenticated,
                    onSettings: _openServerSettings,
                  )
                : _user!['email_verified'] == false
                    ? TuklasVerificationScreen(api: _api, user: _user!, onSignOut: _signOut)
                    : _AuthenticatedShell(
                        api: _api,
                        user: _user!,
                        theme: theme,
                        onSignOut: _signOut,
                        onUserUpdated: (user) => setState(() => _user = user),
                        onSettings: _openServerSettings,
                        onOpenChat: _openChat,
                      ),
      ),
    );
  }
}

class _GuestShell extends StatefulWidget {
  const _GuestShell({
    required this.api,
    required this.theme,
    required this.initialSocialError,
    required this.onAuthenticated,
    required this.onSettings,
  });

  final TuklasApi api;
  final TuklasThemeController theme;
  final String? initialSocialError;
  final ValueChanged<Map<String, dynamic>> onAuthenticated;
  final VoidCallback onSettings;

  @override
  State<_GuestShell> createState() => _GuestShellState();
}

class _GuestShellState extends State<_GuestShell> {
  int _tab = 0;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    final message = widget.initialSocialError;
    if (message != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
      });
    }
  }

  void _openAuth(bool register) {
    Navigator.of(context).push(MaterialPageRoute<void>(
      builder: (_) => TuklasAuthScreen(
        api: widget.api,
        initialMode: register,
        onAuthenticated: widget.onAuthenticated,
        onSocialSignIn: _socialSignIn,
        onBack: () => Navigator.of(context).pop(),
      ),
    ));
  }

  Future<void> _socialSignIn(String provider) async {
    await launchUrl(widget.api.socialLoginUrl(provider), mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: IndexedStack(
          index: _tab,
          children: [
            TuklasLandingScreen(
              onLogin: () => _openAuth(false),
              onRegister: () => _openAuth(true),
              onCatalog: () => setState(() => _tab = 1),
              onSettings: widget.onSettings,
              isDark: widget.theme.mode == ThemeMode.dark,
              onToggleTheme: widget.theme.toggle,
            ),
            TuklasCatalogScreen(api: widget.api),
          ],
        ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _tab,
          onDestinationSelected: (index) => setState(() => _tab = index),
          destinations: const [
            NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home_rounded), label: 'Home'),
            NavigationDestination(icon: Icon(Icons.school_outlined), selectedIcon: Icon(Icons.school), label: 'TESDA'),
          ],
        ),
      );
}

class _AuthenticatedShell extends StatefulWidget {
  const _AuthenticatedShell({
    required this.api,
    required this.user,
    required this.theme,
    required this.onSignOut,
    required this.onUserUpdated,
    required this.onSettings,
    required this.onOpenChat,
  });

  final TuklasApi api;
  final Map<String, dynamic> user;
  final TuklasThemeController theme;
  final VoidCallback onSignOut;
  final ValueChanged<Map<String, dynamic>> onUserUpdated;
  final VoidCallback onSettings;
  final VoidCallback onOpenChat;

  @override
  State<_AuthenticatedShell> createState() => _AuthenticatedShellState();
}

class _AuthenticatedShellState extends State<_AuthenticatedShell> {
  int _tab = 0;

  void _navigate(String destination) => setState(() => _tab = switch (destination) {
        'scanner' => 1,
        'tesda' => 2,
        'profile' => 3,
        _ => 0,
      });

  @override
  Widget build(BuildContext context) {
    final accent = switch ('${widget.user['role'] ?? 'youth'}') {
      'trainer' => TuklasColors.peri,
      'super_admin' => TuklasColors.violet,
      _ => TuklasColors.pink,
    };
    final pages = [
      TuklasDashboardScreen(api: widget.api, onNavigate: _navigate, onOpenChat: widget.onOpenChat),
      TuklasScannerScreen(api: widget.api),
      TuklasCatalogScreen(api: widget.api),
      TuklasProfileScreen(api: widget.api, user: widget.user, onUserUpdated: widget.onUserUpdated, onSignOut: widget.onSignOut),
    ];

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 20,
        title: const _NativeWordmark(),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 4),
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
            decoration: BoxDecoration(color: accent.withValues(alpha: .12), borderRadius: BorderRadius.circular(99)),
            child: Text('${widget.user['role_label'] ?? 'Youth'}', style: TextStyle(color: accent, fontSize: 10, fontWeight: FontWeight.w600)),
          ),
          IconButton(
            tooltip: widget.theme.mode == ThemeMode.dark ? 'Use light mode' : 'Use dark mode',
            onPressed: widget.theme.toggle,
            icon: Icon(widget.theme.mode == ThemeMode.dark ? Icons.light_mode_outlined : Icons.dark_mode_outlined),
          ),
          IconButton(tooltip: 'Server settings', onPressed: widget.onSettings, icon: const Icon(Icons.tune)),
          const SizedBox(width: 5),
        ],
      ),
      body: IndexedStack(index: _tab, children: pages),
      floatingActionButton: _tab == 0
          ? FloatingActionButton.extended(
              onPressed: widget.onOpenChat,
              backgroundColor: TuklasColors.deep,
              foregroundColor: Colors.white,
              icon: const Icon(Icons.auto_awesome),
              label: const Text('Gemini'),
            )
          : null,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _tab,
        onDestinationSelected: (index) => setState(() => _tab = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Overview'),
          NavigationDestination(icon: Icon(Icons.document_scanner_outlined), selectedIcon: Icon(Icons.document_scanner), label: 'Scanner'),
          NavigationDestination(icon: Icon(Icons.school_outlined), selectedIcon: Icon(Icons.school), label: 'TESDA'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Profile'),
        ],
      ),
    );
  }
}

class _NativeWordmark extends StatelessWidget {
  const _NativeWordmark();

  @override
  Widget build(BuildContext context) => const Text(
        'tuklas',
        style: TextStyle(fontSize: 24, fontWeight: FontWeight.w500, letterSpacing: -1.2),
      );

}
