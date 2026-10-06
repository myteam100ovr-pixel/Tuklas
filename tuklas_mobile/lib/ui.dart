import 'package:flutter/material.dart';

import 'api.dart';
import 'theme.dart';

String tkInitials(String name) {
  final parts = name
      .trim()
      .split(RegExp(r'[\s._-]+'))
      .where((p) => p.isNotEmpty)
      .take(2);
  return parts.map((p) => p[0].toUpperCase()).join();
}

IconData tkIcon(String name) {
  switch (name) {
    case 'user':
      return Icons.person_outline;
    case 'scan':
      return Icons.description_outlined;
    case 'briefcase':
      return Icons.work_outline;
    case 'cap':
      return Icons.school_outlined;
    case 'users':
      return Icons.groups_outlined;
    case 'clipboard':
      return Icons.assignment_outlined;
    default:
      return Icons.circle_outlined;
  }
}

Map<String, dynamic> asMap(dynamic value) =>
    value is Map ? Map<String, dynamic>.from(value) : <String, dynamic>{};

List<dynamic> asList(dynamic value) => value is List ? value : <dynamic>[];

BoxDecoration _navigationPanelDecoration(BuildContext context) => BoxDecoration(
  color: TkColors.of(context).surface,
  border: Border.all(color: TkColors.of(context).line),
  borderRadius: BorderRadius.circular(18),
);

class TkCard extends StatelessWidget {
  const TkCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.fromLTRB(20, 18, 20, 20),
  });

  final Widget child;
  final EdgeInsetsGeometry padding;

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    return Container(
      width: double.infinity,
      padding: padding,
      decoration: BoxDecoration(
        color: c.surface,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: c.line),
        boxShadow: c.isDark
            ? null
            : const [
                BoxShadow(
                  color: Color(0x0D140A3C),
                  blurRadius: 28,
                  offset: Offset(0, 10),
                ),
              ],
      ),
      child: child,
    );
  }
}

class TkChip extends StatelessWidget {
  const TkChip({super.key, required this.label, this.tone = 'neutral'});

  final String label;
  final String tone;

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    Color bg = c.surface2;
    Color fg = c.ink2;
    if (tone == 'good') {
      bg = c.goodBg;
      fg = c.good;
    } else if (tone == 'warn') {
      bg = c.warnBg;
      fg = c.warn;
    } else if (tone == 'bad') {
      bg = c.badBg;
      fg = c.bad;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label,
        style: tkText(context, size: 11.5, weight: 500, color: fg),
      ),
    );
  }
}

class TkAvatar extends StatelessWidget {
  const TkAvatar({super.key, required this.initials, this.size = 43});

  final String initials;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      decoration: const BoxDecoration(
        color: TkColors.violet,
        shape: BoxShape.circle,
      ),
      child: Text(
        initials,
        style: tkText(context, size: 13.5, weight: 500, color: Colors.white),
      ),
    );
  }
}

class TkIconButton extends StatelessWidget {
  const TkIconButton({
    super.key,
    required this.icon,
    required this.tooltip,
    required this.onTap,
  });

  final IconData icon;
  final String tooltip;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: onTap,
        customBorder: const CircleBorder(),
        child: Container(
          width: 43,
          height: 43,
          decoration: BoxDecoration(
            color: c.surface,
            shape: BoxShape.circle,
            border: Border.all(color: c.line),
          ),
          child: Icon(icon, size: 19, color: c.ink2),
        ),
      ),
    );
  }
}

/// Branded app header with a quick theme control.
class TkHeader extends StatelessWidget {
  const TkHeader({super.key});

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
      child: Container(
        key: const ValueKey('top-navigation-panel'),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: _navigationPanelDecoration(context),
        child: Row(
          children: [
            Text(
              'tuklas',
              style: tkText(
                context,
                size: 27,
                weight: 500,
                spacing: -1.4,
                height: 1,
              ),
            ),
            const Spacer(),
            TkIconButton(
              icon: dark ? Icons.dark_mode_outlined : Icons.light_mode_outlined,
              tooltip: 'Light / dark mode',
              onTap: () => themeController.toggle(context),
            ),
          ],
        ),
      ),
    );
  }
}

class TkBottomNavigation extends StatelessWidget {
  const TkBottomNavigation({
    super.key,
    required this.selectedIndex,
    required this.onDestinationSelected,
  });

  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    final user = ApiClient.instance.user ?? <String, dynamic>{};
    final name = '${user['name'] ?? ''}';

    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
        child: Container(
          key: const ValueKey('bottom-navigation-panel'),
          decoration: _navigationPanelDecoration(context),
          clipBehavior: Clip.antiAlias,
          child: NavigationBarTheme(
            data: NavigationBarThemeData(
              iconTheme: WidgetStateProperty.resolveWith((states) {
                if (states.contains(WidgetState.selected)) {
                  return const IconThemeData(color: Colors.white);
                }
                return IconThemeData(color: c.muted);
              }),
            ),
            child: NavigationBar(
              selectedIndex: selectedIndex,
              onDestinationSelected: onDestinationSelected,
              backgroundColor: c.surface,
              indicatorColor: TkColors.violet,
              labelBehavior: NavigationDestinationLabelBehavior.alwaysHide,
              labelPadding: EdgeInsets.zero,
              labelTextStyle: const WidgetStatePropertyAll(
                TextStyle(fontSize: 0, height: 0),
              ),
              animationDuration: const Duration(milliseconds: 180),
              elevation: 0,
              height: 64,
              destinations: [
                NavigationDestination(
                  icon: const Icon(Icons.dashboard_outlined),
                  selectedIcon: const Icon(Icons.dashboard),
                  label: '',
                  tooltip: 'Overview',
                ),
                const NavigationDestination(
                  icon: Icon(Icons.work_outline),
                  selectedIcon: Icon(Icons.work),
                  label: '',
                  tooltip: 'PESO',
                ),
                const NavigationDestination(
                  icon: Icon(Icons.document_scanner_outlined),
                  selectedIcon: Icon(Icons.document_scanner),
                  label: '',
                  tooltip: 'Scanner',
                ),
                const NavigationDestination(
                  icon: Icon(Icons.school_outlined),
                  selectedIcon: Icon(Icons.school),
                  label: '',
                  tooltip: 'TESDA',
                ),
                NavigationDestination(
                  key: const ValueKey('profile-destination'),
                  icon: TkAvatar(initials: tkInitials(name), size: 34),
                  selectedIcon: TkAvatar(initials: tkInitials(name), size: 34),
                  label: '',
                  tooltip: 'Profile',
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
