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
import 'patch_notes_view.dart';

/// Branded header and bottom navigation around the selected app section.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> with WidgetsBindingObserver {
  bool _checkingVerification = false;
  bool _checkingAssessment = false;
  bool _assessmentRequired = false;
  String? _verificationMessage;
  int _tab = 0;
  String? _activeGroupItem;
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
    if (_role == 'youth' &&
        ApiClient.instance.user?['email_verified'] != false) {
      _checkingAssessment = true;
      _assessmentRequired = true;
      unawaited(_checkSkillsAssessment());
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

  Future<void> _checkSkillsAssessment() async {
    try {
      final result = await ApiClient.instance.workspace('/assessment');
      if (mounted) {
        setState(() {
          _assessmentRequired = result['assessmentComplete'] != true;
          _checkingAssessment = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _checkingAssessment = false);
    }
  }

  void _handleScanComplete() {
    if (mounted) setState(() => _version++);
    unawaited(_loadUnreadNotifications());
  }

  String get _role => '${ApiClient.instance.user?['role']}';

  List<String> get _groupItems => switch ((_role, _tab)) {
    ('admin', 2) => ['Scholarships', 'Course catalog', 'Career paths'],
    ('admin', 4) => [
      'Openings',
      'Centers',
      'Trainers',
      'Dates',
      'Requests',
      'Results',
      'PESO AI',
      'Messages',
      'Notifications',
      'TuklasBot',
      'Profile',
      'Account & security',
      'Patch Notes',
    ],
    ('youth', 2) => [
      'Courses',
      'Scholarships',
      'Centers',
      'Dates',
      'My requests',
      'My results',
      'Skills assessment',
      'Training plans',
    ],
    ('youth', 3) => ['Messages', 'Notifications'],
    ('youth', 4) => [
      'Profile',
      'TuklasBot',
      'Account & security',
      'Patch Notes',
    ],
    ('tesda_admin', 1) => [
      'Centers',
      'My courses',
      'Assign trainers',
      'Dates',
      'Requests',
      'Results',
    ],
    ('tesda_admin', 2) => ['Course catalog', 'Scholarships'],
    ('tesda_admin', 3) => ['Messages', 'Notifications'],
    ('tesda_admin', 4) => [
      'Profile',
      'TuklasBot',
      'Account & security',
      'Patch Notes',
    ],
    _ => const [],
  };

  String get _defaultGroupItem => switch ((_role, _tab)) {
    ('admin', 2) => 'Scholarships',
    ('admin', 4) => 'Centers',
    ('youth', 2) => 'Courses',
    ('youth', 3) => 'Messages',
    ('tesda_admin', 1) => 'Centers',
    ('tesda_admin', 2) => 'Course catalog',
    ('tesda_admin', 3) => 'Messages',
    ('tesda_admin', 4) => 'Profile',
    _ => '',
  };

  Widget _selectedView() {
    if (_tab == 0) {
      return DashboardView(
        key: ValueKey(_version),
        onEditProfile: () => _openWorkspace('Profile'),
        onOpenPeso: () => _openWorkspace('PESO AI'),
        onOpenScanner: () => _openWorkspace('Document Scanner'),
        onOpenTesda: () => _openWorkspace('Course catalog'),
        onLogout: _logout,
        onOpenWorkspace: _openWorkspace,
      );
    }
    if (_groupItems.isNotEmpty && _activeGroupItem == null) {
      return _NavigationHub(
        title: _hubTitle,
        items: _groupItems,
        unreadNotifications: _unreadNotifications,
        onSelect: _openGroupedItem,
      );
    }
    if (_role == 'admin' && _tab == 1) {
      return const OperationsView(section: 'Job applications');
    }
    if (_role == 'admin' && _tab == 3) {
      return const WorkspaceView(section: 'Users');
    }
    if (_role == 'youth' && _tab == 1) {
      return const OperationsView(section: 'Jobs');
    }
    final item = _activeGroupItem ?? _defaultGroupItem;
    return switch (item) {
      'PESO AI' => const PesoView(),
      'Courses' || 'Course catalog' => const TesdaView(),
      'My courses' => const WorkspaceView(section: 'My programs'),
      'Document Scanner' => ScannerView(
        key: const ValueKey('scanner'),
        onScanComplete: _handleScanComplete,
      ),
      'Messages' => const MessagesView(),
      'Notifications' => const WorkspaceView(section: 'Notifications'),
      'Users' => const WorkspaceView(section: 'Users'),
      'Career paths' ||
      'Careers' => const OperationsView(section: 'Career paths'),
      'Training plans' || 'Skills assessment' => WorkspaceView(section: item),
      'TuklasBot' => const ChatView(),
      'Profile' => ProfileView(
        onSaved: () => setState(() => _version++),
        onLogout: _logout,
      ),
      'Account & security' => const WorkspaceView(
        section: 'Account & security',
      ),
      'Patch Notes' => const PatchNotesView(),
      'Dates' => const OperationsView(section: 'Sessions'),
      'Applications' => const OperationsView(section: 'Job applications'),
      'Requests' ||
      'My requests' => const OperationsView(section: 'Applications'),
      'Results' || 'My results' => const OperationsView(section: 'Assessments'),
      'Assign trainers' => const OperationsView(section: 'Trainers'),
      'Openings' || 'Find jobs' => const OperationsView(section: 'Jobs'),
      _ => OperationsView(section: item),
    };
  }

  String get _hubTitle => switch ((_role, _tab)) {
    ('admin', 2) => 'Programs',
    ('admin', 4) => 'More tools',
    ('youth', 2) => 'Training',
    ('youth', 3) => 'Inbox',
    ('youth', 4) => 'Profile and settings',
    ('tesda_admin', 1) => 'Training',
    ('tesda_admin', 2) => 'Programs',
    ('tesda_admin', 3) => 'Inbox',
    ('tesda_admin', 4) => 'More tools',
    _ => 'Workspace',
  };

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

  Future<void> _openWorkspace(
    String section, {
    String? initialQuery,
    bool allowNested = false,
  }) async {
    if (_switching && !allowNested) return;
    setState(() => _switching = true);
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => WorkspaceRefreshScope(
          child: switch (section) {
            'TuklasBot' => ChatView(onOpenLink: _openSystemLink),
            'Patch Notes' => const PatchNotesView(),
            'Messages' => const MessagesView(),
            'PESO AI' => const PesoView(),
            'Career paths' || 'Careers' => OperationsView(
              section: 'Career paths',
              initialQuery: initialQuery,
            ),
            'Course catalog' ||
            'Courses' => TesdaView(initialQuery: initialQuery),
            'Document Scanner' => ScannerView(
              onScanComplete: _handleScanComplete,
              onOpenLink: _openSystemLink,
            ),
            'Openings' || 'Find jobs' => OperationsView(
              section: 'Jobs',
              initialQuery: initialQuery,
            ),
            'Dates' => const OperationsView(section: 'Sessions'),
            'Applications' => const OperationsView(section: 'Job applications'),
            'Requests' ||
            'My requests' => const OperationsView(section: 'Applications'),
            'Results' ||
            'My results' => const OperationsView(section: 'Assessments'),
            'Assign trainers' => const OperationsView(section: 'Trainers'),
            'My courses' => const WorkspaceView(section: 'My programs'),
            'Profile' => ProfileView(
              onSaved: () => setState(() => _version++),
              onLogout: _logout,
            ),
            'Jobs' => OperationsView(
              section: section,
              initialQuery: initialQuery,
            ),
            'Scholarships' => OperationsView(
              section: section,
              initialQuery: initialQuery,
            ),
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

  void _openSystemLink(String rawRoute) {
    final route = Uri.tryParse(rawRoute);
    if (route == null ||
        !route.path.startsWith('/') ||
        route.path.startsWith('//')) {
      return;
    }
    final query = route.queryParameters['q'];
    switch (route.path) {
      case '/dashboard':
        Navigator.of(context).popUntil((page) => page.isFirst);
        setState(() => _tab = 0);
        return;
      case '/courses':
        _openWorkspace('Courses', initialQuery: query, allowNested: true);
        return;
      case '/scholarships':
        _openWorkspace('Scholarships', initialQuery: query, allowNested: true);
        return;
      case '/jobs':
        _openWorkspace('Jobs', initialQuery: query, allowNested: true);
        return;
      case '/applications':
        _openWorkspace(
          ApiClient.instance.user?['role'] == 'youth'
              ? 'My requests'
              : 'Applications',
          allowNested: true,
        );
        return;
      case '/training-requests':
        _openWorkspace('Requests', allowNested: true);
        return;
      case '/assessment-results':
        _openWorkspace(
          ApiClient.instance.user?['role'] == 'youth'
              ? 'My results'
              : 'Results',
          allowNested: true,
        );
        return;
      case '/job-applications':
        _openWorkspace('Applications', allowNested: true);
        return;
      case '/profile':
        _openWorkspace('Profile', allowNested: true);
        return;
      case '/scanner':
        _openWorkspace('Document Scanner', allowNested: true);
        return;
      case '/tuklasbot':
        _openWorkspace('TuklasBot', allowNested: true);
        return;
    }
  }

  Future<void> _selectTab(int index) async {
    if (_switching) return;
    if (index == _tab) {
      if (_activeGroupItem != null) {
        setState(() => _activeGroupItem = null);
      }
      return;
    }
    setState(() {
      _tab = index;
      _activeGroupItem = null;
      _switching = true;
    });
    await WidgetsBinding.instance.endOfFrame;
    if (!mounted) return;
    setState(() => _switching = false);
  }

  void _openGroupedItem(String item) {
    if (_activeGroupItem == item) return;

    setState(() => _activeGroupItem = item);
    if (item == 'Notifications') {
      unawaited(_loadUnreadNotifications());
    }
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
    if (_checkingAssessment) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    if (_assessmentRequired) {
      return WorkspaceRefreshScope(
        child: WorkspaceView(
          section: 'Skills assessment',
          onAssessmentCompleted: () {
            if (mounted) setState(() => _assessmentRequired = false);
          },
          onLogout: _logout,
        ),
      );
    }
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const TkHeader(),
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
                            'Opening ${_tab == 0 ? 'Overview' : (_activeGroupItem ?? _defaultGroupItem)}…',
                          ),
                        ],
                      ),
                    )
                  : KeyedSubtree(
                      key: ValueKey(
                        '$_tab-${_activeGroupItem ?? _defaultGroupItem}',
                      ),
                      child: WorkspaceRefreshScope(child: _selectedView()),
                    ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: TkBottomNavigation(
        selectedIndex: _tab,
        onDestinationSelected: _selectTab,
      ),
      floatingActionButton: _role == 'admin' || _role == 'youth'
          ? FloatingActionButton.extended(
              onPressed: () => _openWorkspace('Document Scanner'),
              icon: const Icon(Icons.document_scanner_outlined),
              label: const Text('Scan document'),
            )
          : null,
    );
  }
}

class _NavigationHub extends StatelessWidget {
  const _NavigationHub({
    required this.title,
    required this.items,
    required this.unreadNotifications,
    required this.onSelect,
  });

  final String title;
  final List<String> items;
  final int unreadNotifications;
  final ValueChanged<String> onSelect;

  IconData _icon(String item) => switch (item) {
    'Scholarships' => Icons.volunteer_activism_outlined,
    'Course catalog' || 'Courses' || 'My courses' => Icons.menu_book_outlined,
    'Career paths' => Icons.route_outlined,
    'Centers' => Icons.location_city_outlined,
    'Trainers' || 'Assign trainers' => Icons.co_present_outlined,
    'Dates' || 'Sessions' => Icons.calendar_month_outlined,
    'Requests' || 'My requests' || 'Applications' => Icons.assignment_outlined,
    'Results' || 'My results' => Icons.fact_check_outlined,
    'PESO AI' => Icons.auto_awesome_outlined,
    'Messages' => Icons.chat_bubble_outline,
    'Notifications' => Icons.notifications_none_outlined,
    'Document Scanner' => Icons.document_scanner_outlined,
    'TuklasBot' => Icons.forum_outlined,
    'Profile' => Icons.person_outline,
    'Account & security' => Icons.security_outlined,
    'Patch Notes' => Icons.new_releases_outlined,
    _ => Icons.circle_outlined,
  };

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(title)),
    body: ListView(
      padding: const EdgeInsets.all(16),
      children: [
        for (final item in items)
          Card(
            margin: const EdgeInsets.only(bottom: 8),
            child: ListTile(
              leading: Icon(_icon(item)),
              title: Text(item),
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (item == 'Notifications' && unreadNotifications > 0)
                    Badge(label: Text('$unreadNotifications')),
                  const SizedBox(width: 8),
                  const Icon(Icons.chevron_right),
                ],
              ),
              onTap: () => onSelect(item),
            ),
          ),
      ],
    ),
  );
}
