import 'package:flutter/material.dart';

import '../api.dart';
import '../ui.dart';
import 'dashboard_view.dart';
import 'login_screen.dart';
import 'profile_view.dart';
import 'peso_view.dart';
import 'scanner_view.dart';
import 'tesda_view.dart';

/// Branded header and bottom navigation around the selected app section.
class HomeShell extends StatefulWidget {
  const HomeShell({super.key});

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _tab = 0;
  int _version = 0; // bumped after a profile save so the dashboard reloads

  Future<void> _logout() async {
    await ApiClient.instance.logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (_) => false,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const TkHeader(),
            Expanded(
              child: switch (_tab) {
                1 => const PesoView(),
                2 => ScannerView(
                  key: ValueKey('scanner-$_version'),
                  onScanComplete: () => setState(() => _version++),
                ),
                3 => const TesdaView(),
                4 => ProfileView(
                  onSaved: () => setState(() => _version++),
                  onLogout: _logout,
                ),
                _ => DashboardView(
                  key: ValueKey(_version),
                  onEditProfile: () => setState(() => _tab = 4),
                  onOpenPeso: () => setState(() => _tab = 1),
                  onOpenScanner: () => setState(() => _tab = 2),
                  onOpenTesda: () => setState(() => _tab = 3),
                  onLogout: _logout,
                ),
              },
            ),
          ],
        ),
      ),
      bottomNavigationBar: TkBottomNavigation(
        selectedIndex: _tab,
        onDestinationSelected: (index) => setState(() => _tab = index),
      ),
    );
  }
}
