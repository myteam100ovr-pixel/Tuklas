import 'dart:async';

import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';
import '../workspace_realtime.dart';
import 'dashboard_view.dart';
import 'login_screen.dart';
import 'profile_view.dart';
import 'peso_view.dart';
import 'scanner_view.dart';
import 'tesda_view.dart';
import 'workspace_view.dart';
import 'chat_view.dart';
import 'messages_view.dart';
import 'operations_view.dart';

/// Branded header and bottom navigation around the selected app section.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> with WidgetsBindingObserver {
  bool _checkingVerification = false;
  String? _verificationMessage;
  int _tab = 0;
  bool _switching = false;
  int _version = 0; // bumped after a profile save so the dashboard reloads
  int _unreadNotifications = 0;
  Timer? _notificationTimer;
  bool _checkingNotifications = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WorkspaceRealtime.instance.revision.addListener(_onWorkspaceChanged);
    if (ApiClient.instance.user?['email_verified'] != false) {
      unawaited(WorkspaceRealtime.instance.connect());
    }
    _loadUnreadNotifications();
    _notificationTimer = Timer.periodic(const Duration(seconds: 30), (_) {
      if (!WorkspaceRealtime.instance.isConnected) {
        _loadUnreadNotifications();
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    WorkspaceRealtime.instance.revision.removeListener(_onWorkspaceChanged);
    _notificationTimer?.cancel();
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      WorkspaceRealtime.instance.reconnect();
      _loadUnreadNotifications();
    }
  }

  void _onWorkspaceChanged() {
    if (WorkspaceRealtime.instance.latestResource == 'users') {
      unawaited(_refreshAccount());
    }
    if (mounted) unawaited(_loadUnreadNotifications());
  }

  Future<void> _refreshAccount() async {
    final previousName = ApiClient.instance.user?['name'];
    try {
      final user = await ApiClient.instance.me();
      if (mounted && user['name'] != previousName) {
        setState(() => _version++);
      }
    } catch (_) {
      // Keep the signed-in account cached until the server is reachable again.
    }
  }

  Future<void> _loadUnreadNotifications() async {
    if (_checkingNotifications ||
        ApiClient.instance.user?['email_verified'] == false) {
      return;
    }
    _checkingNotifications = true;
    try {
      final result = await ApiClient.instance.workspace('/notifications');
      if (mounted) {
        setState(
          () => _unreadNotifications =
              (result['unread_count'] as num?)?.toInt() ?? 0,
        );
      }
    } catch (_) {
      // Keep the latest known count until the connection recovers.
    } finally {
      _checkingNotifications = false;
    }
  }

  bool get _careerTools =>
      ['youth', 'admin'].contains(ApiClient.instance.user?['role']);

  Widget _selectedView() {
    final role = '${ApiClient.instance.user?['role']}';
    if (_tab == 4) {
      return ProfileView(
        onSaved: () => setState(() => _version++),
        onLogout: _logout,
      );
    }
    if (_careerTools) {
      return switch (_tab) {
        1 => const PesoView(),
        2 => ScannerView(
          key: ValueKey('scanner-$_version'),
          onScanComplete: () => setState(() => _version++),
        ),
        3 => const TesdaView(),
        _ => DashboardView(
          key: ValueKey(_version),
          onEditProfile: () => _selectTab(4),
          onOpenPeso: () => _selectTab(1),
          onOpenScanner: () => _selectTab(2),
          onOpenTesda: () => _selectTab(3),
          onLogout: _logout,
          onOpenWorkspace: _openWorkspace,
        ),
      };
    }
    if (role == 'tesda_admin') {
      return switch (_tab) {
        1 => const WorkspaceView(section: 'My programs'),
        2 => const OperationsView(section: 'Sessions'),
        3 => const TesdaView(),
        _ => DashboardView(
          key: ValueKey(_version),
          onEditProfile: () => _selectTab(4),
          onOpenPeso: () => _selectTab(1),
          onOpenScanner: () => _openWorkspace('My programs'),
          onOpenTesda: () => _selectTab(3),
          onLogout: _logout,
          onOpenWorkspace: _openWorkspace,
        ),
      };
    }
    return switch (_tab) {
      1 => const WorkspaceView(section: 'My programs'),
      2 => const OperationsView(section: 'Sessions'),
      3 => const MessagesView(),
      _ => DashboardView(
        key: ValueKey(_version),
        onEditProfile: () => _selectTab(4),
        onOpenPeso: () => _openWorkspace('My programs'),
        onOpenScanner: () => _openWorkspace('Sessions'),
        onOpenTesda: () => _openWorkspace('Messages'),
        onLogout: _logout,
        onOpenWorkspace: _openWorkspace,
      ),
    };
  }

  Future<void> _checkVerification({bool resend = false}) async {
    if (_checkingVerification) return;
    setState(() {
      _checkingVerification = true;
      _verificationMessage = null;
    });
    try {
      if (resend) {
        final result = await ApiClient.instance.workspace(
          '/email/verification-notification',
          method: 'POST',
        );
        if (mounted) {
          setState(() => _verificationMessage = result['message'] as String?);
        }
      } else {
        await ApiClient.instance.me();
        if (ApiClient.instance.user?['email_verified'] != false) {
          unawaited(WorkspaceRealtime.instance.connect());
        }
        if (mounted) {
          setState(
            () => _verificationMessage = 'If you have not verified yet, open the link in your email and check again.',
          );
        }
      }
    } on ApiException catch (error) {
      if (mounted) setState(() => _verificationMessage = error.message);
    } catch (_) {
      if (mounted) {
        setState(
          () => _verificationMessage = 'Could not connect. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _checkingVerification = false);
    }
  }

  Future<void> _logout() async {
    await WorkspaceRealtime.instance.disconnect();
    await ApiClient.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  Future<void> _openWorkspace(String section) async {
    if (_switching) return;
    setState(() => _switching = true);
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => WorkspaceRefreshScope(
          child: switch (section) {
            'TuklasBot' => const ChatView(),
            'Messages' => const MessagesView(),
            'Jobs' ||
            'Scholarships' ||
            'Centers' ||
            'Trainers' ||
            'Sessions' ||
            'Applications' ||
            'Assessments' => OperationsView(section: section),
            _ => WorkspaceView(section: section),
          },
        ),
      ),
    );
    _loadUnreadNotifications();
    if (mounted) {
      setState(() {
        _switching = false;
        _version++;
      });
    }
  }

  Future<void> _selectTab(int index) async {
    if (_switching || index == _tab) return;
    setState(() {
      _tab = index;
      _switching = true;
    });
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    setState(() => _switching = false);
  }

  @override
  Widget build(BuildContext context) {
    if (ApiClient.instance.user?['email_verified'] == false) {
      return Scaffold(
        body: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.mark_email_unread_outlined, size: 48),
                    const SizedBox(height: 20),
                    Text(
                      'Verify your email',
                      style: Theme.of(context).textTheme.headlineSmall,
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'Open the verification link in your email to access your workspace.',
                      textAlign: TextAlign.center,
                    ),
                    if (_verificationMessage != null) ...[
                      const SizedBox(height: 16),
                      Semantics(
                        liveRegion: true,
                        child: Text(
                          _verificationMessage!,
                          textAlign: TextAlign.center,
                        ),
                      ),
                    ],
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _checkingVerification
                          ? null
                          : () => _checkVerification(),
                      child: Text(
                        _checkingVerification
                            ? 'Please wait…'
                            : 'I have verified my email',
                      ),
                    ),
                    const SizedBox(height: 12),
                    OutlinedButton(
                      onPressed: _checkingVerification
                          ? null
                          : () => _checkVerification(resend: true),
                      child: const Text('Resend verification email'),
                    ),
                    TextButton(
                      onPressed: _checkingVerification ? null : _logout,
                      child: const Text('Log out'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      );
    }
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const TkHeader(),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: Row(
                children: [
                  ActionChip(
                    label: const Text('Messages'),
                    onPressed: () => _openWorkspace('Messages'),
                  ),
                  const SizedBox(width: 8),
                  ActionChip(
                    label: const Text('Jobs'),
                    onPressed: () => _openWorkspace('Jobs'),
                  ),
                  const Spacer(),
                  PopupMenuButton<String>(
                    tooltip: 'Open a Tuklas tool',
                    onSelected: _openWorkspace,
                    itemBuilder: (_) => [
                      for (final section in [
                        'TuklasBot',
                        'Notifications',
                        'Training plans',
                        if (ApiClient.instance.user?['role'] == 'youth')
                          'Skills assessment',
                        if (['tesda_admin']
                            .contains(ApiClient.instance.user?['role']))
                          'My programs',
                        if (ApiClient.instance.user?['role'] == 'admin')
                          'Users',
                        if ([
                          'admin',
                          'tesda_admin',
                        ].contains(ApiClient.instance.user?['role']))
                          'Scholarships',
                        'Centers',
                        if ([
                          'admin',
                          'tesda_admin',
                        ].contains(ApiClient.instance.user?['role']))
                          'Trainers',
                        'Sessions',
                        if ([
                          'admin',
                          'tesda_admin',
                          'youth',
                        ].contains(ApiClient.instance.user?['role'])) ...[
                          'Applications',
                          'Assessments',
                        ],
                        'Account & security',
                      ])
                        PopupMenuItem(value: section, child: Text(section)),
                    ],
                    child: Chip(
                      label: Text(
                        _unreadNotifications > 0
                            ? 'More tools ($_unreadNotifications) ▾'
                            : 'More tools ▾',
                      ),
                    ),
                  ),
                ],
              ),
            ),
            Expanded(
              child: _switching
                  ? Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (!motionController.enabled)
                            const CircularProgressIndicator()
                          else
                            const Icon(Icons.hourglass_empty_outlined),
                          const SizedBox(height: 12),
                          Text(
                            'Opening ${const ['Overview', 'PESO AI or training', 'Scanner or sessions', 'TESDA or messages', 'Profile'][_tab]}…',
                          ),
                        ],
                      ),
                    )
                  : WorkspaceRefreshScope(child: _selectedView()),
            ),
          ],
        ),
      ),
      bottomNavigationBar: TkBottomNavigation(
        selectedIndex: _tab,
        onDestinationSelected: _selectTab,
      ),
    );
  }
}
