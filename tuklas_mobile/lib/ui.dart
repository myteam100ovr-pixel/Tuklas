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

class TkPageHeading extends StatelessWidget {
  const TkPageHeading({super.key, required this.eyebrow, required this.title});

  final String eyebrow;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 16, bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            eyebrow,
            style: tkText(
              context,
              size: 10,
              weight: 600,
              spacing: 1.5,
              color: TkColors.of(context).taskMeta,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            title,
            style: tkText(
              context,
              size: 30,
              weight: 600,
              spacing: -1,
              height: 1.15,
            ),
          ),
        ],
      ),
    );
  }
}

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
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: c.line),
        boxShadow: c.isDark
            ? null
            : const [
                BoxShadow(
                  color: Color(0x06140A3C),
                  blurRadius: 12,
                  offset: Offset(0, 4),
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
            Image.asset(
              'assets/images/tuklas-logo.png',
              width: 30,
              height: 30,
              fit: BoxFit.contain,
            ),
            const SizedBox(width: 8),
            Text(
              'Tuklas.',
              style: tkText(
                context,
                size: 27,
                weight: 700,
                spacing: -1.4,
                height: 1,
              ),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: TkColors.violet.withValues(alpha: .09),
                borderRadius: BorderRadius.circular(30),
              ),
              child: Text(
                'WORKSPACE',
                style: tkText(
                  context,
                  size: 9,
                  weight: 600,
                  spacing: 1,
                  color: TkColors.of(context).taskMeta,
                ),
              ),
            ),
            const SizedBox(width: 10),
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
    final role = '${user['role'] ?? ''}';
    final destinations = role == 'tesda_admin'
        ? const [
            ('Home', Icons.dashboard_outlined, Icons.dashboard),
            ('Training', Icons.school_outlined, Icons.school),
            ('Programs', Icons.menu_book_outlined, Icons.menu_book),
            ('Inbox', Icons.forum_outlined, Icons.forum),
            ('Account', Icons.person_outline, Icons.person),
          ]
        : const [
            ('Home', Icons.dashboard_outlined, Icons.dashboard),
            ('Jobs', Icons.work_outline, Icons.work),
            ('Training', Icons.school_outlined, Icons.school),
            ('Inbox', Icons.forum_outlined, Icons.forum),
            (
              'Scanner',
              Icons.document_scanner_outlined,
              Icons.document_scanner,
            ),
            ('More', Icons.grid_view_outlined, Icons.grid_view),
          ];

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
              labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
              labelPadding: EdgeInsets.zero,
              labelTextStyle: const WidgetStatePropertyAll(
                TextStyle(fontSize: 10),
              ),
              animationDuration: motionController.enabled
                  ? Duration.zero
                  : const Duration(milliseconds: 180),
              elevation: 0,
              height: 64,
              destinations: [
                for (final destination in destinations)
                  NavigationDestination(
                    icon: Icon(destination.$2),
                    selectedIcon: Icon(destination.$3),
                    label: destination.$1,
                    tooltip: destination.$1,
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Page controls that stay usable when a card has very little horizontal space.
class TkPagination extends StatelessWidget {
  const TkPagination({
    super.key,
    required this.currentPage,
    required this.lastPage,
    required this.onPrevious,
    required this.onNext,
    this.outlined = false,
  });

  final int currentPage;
  final int lastPage;
  final VoidCallback? onPrevious;
  final VoidCallback? onNext;
  final bool outlined;

  @override
  Widget build(BuildContext context) {
    final c = TkColors.of(context);
    final label = Text(
      'Page $currentPage of $lastPage',
      textAlign: TextAlign.center,
      style: tkText(context, size: 12.5, color: c.muted),
    );

    Widget pageButton(String text, VoidCallback? onPressed) => outlined
        ? OutlinedButton(onPressed: onPressed, child: Text(text))
        : TextButton(onPressed: onPressed, child: Text(text));

    return LayoutBuilder(
      builder: (context, constraints) {
        if (constraints.maxWidth < 360) {
          return Column(
            children: [
              label,
              const SizedBox(height: 4),
              Row(
                children: [
                  Expanded(child: pageButton('Previous', onPrevious)),
                  const SizedBox(width: 8),
                  Expanded(child: pageButton('Next', onNext)),
                ],
              ),
            ],
          );
        }

        return Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            pageButton('Previous', onPrevious),
            label,
            pageButton('Next', onNext),
          ],
        );
      },
    );
  }
}

String tkSkillCoverageDetails(Map<dynamic, dynamic> item) {
  final coverage = asMap(item['skillCoverage']);
  return [
    if (coverage.isNotEmpty)
      '${coverage['percent']}% documented skill coverage (${coverage['matched']} of ${coverage['total']} listed skills)',
    if (asList(item['evidencedSkills']).isNotEmpty)
      'Documented: ${asList(item['evidencedSkills']).join(', ')}',
    if (asList(item['skillsToDevelop']).isNotEmpty)
      'To develop: ${asList(item['skillsToDevelop']).join(', ')}',
  ].join('\n');
}
