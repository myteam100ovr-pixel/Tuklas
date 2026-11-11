import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';
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

class _HomeShellState extends State<HomeShell> {
  bool _checkingVerification = false;
  String? _verificationMessage;
  int _tab = 0;
  bool _switching = false;
  int _version = 0; // bumped after a profile save so the dashboard reloads

  bool get _careerTools =>
      ['youth', 'super_admin'].contains(ApiClient.instance.user?['role']);

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
    if (role == 'trainer') {
      return switch (_tab) {
        1 => const WorkspaceView(section: 'My programs'),
        2 => const MessagesView(),
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
        builder: (_) => switch (section) {
          'TuklasBot' => const ChatView(),
          'Messages' => const MessagesView(),
          'Employers' ||
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
    );
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
                        if ([
                          'trainer',
                          'tesda_admin',
                          'center_representative',
                        ].contains(ApiClient.instance.user?['role']))
                          'My programs',
                        if (ApiClient.instance.user?['role'] == 'super_admin')
                          'Users',
                        if (ApiClient.instance.user?['role'] == 'super_admin')
                          'Employers',
                        'Scholarships',
                        'Centers',
                        if ([
                          'super_admin',
                          'tesda_admin',
                          'center_representative',
                        ].contains(ApiClient.instance.user?['role']))
                          'Trainers',
                        'Sessions',
                        if (ApiClient.instance.user?['role'] != 'trainer') ...[
                          'Applications',
                          'Assessments',
                        ],
                        'Account & security',
                      ])
                        PopupMenuItem(value: section, child: Text(section)),
                    ],
                    child: const Chip(label: Text('More tools ▾')),
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
                  : _selectedView(),
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
