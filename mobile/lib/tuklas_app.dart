import 'package:flutter/material.dart';

const _ink = Color(0xFF0D0D0F);
const _deep = Color(0xFF4510B9);
const _violet = Color(0xFF5B17FF);
const _pink = Color(0xFFF55BA8);
const _yellow = Color(0xFFFFD92E);
const _gray = Color(0xFFF7F7F8);
const _line = Color(0xFFEBEBEE);
const _muted = Color(0xFF7A7A86);

class TuklasApp extends StatelessWidget {
  const TuklasApp({super.key});

  @override
  Widget build(BuildContext context) {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: _violet,
      brightness: Brightness.light,
    );

    return MaterialApp(
      title: 'Tuklas',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: colorScheme.copyWith(
          primary: _violet,
          secondary: _pink,
          surface: Colors.white,
        ),
        scaffoldBackgroundColor: Colors.white,
        appBarTheme: const AppBarTheme(
          backgroundColor: Colors.white,
          foregroundColor: _ink,
          elevation: 0,
          scrolledUnderElevation: 0,
        ),
        navigationBarTheme: NavigationBarThemeData(
          backgroundColor: Colors.white,
          indicatorColor: _pink.withValues(alpha: .14),
          labelTextStyle: WidgetStateProperty.resolveWith((states) {
            return TextStyle(
              color: states.contains(WidgetState.selected) ? _violet : _muted,
              fontSize: 11,
              fontWeight: states.contains(WidgetState.selected)
                  ? FontWeight.w700
                  : FontWeight.w500,
            );
          }),
        ),
      ),
      home: const TuklasHome(),
    );
  }
}

class TuklasHome extends StatefulWidget {
  const TuklasHome({super.key});

  @override
  State<TuklasHome> createState() => _TuklasHomeState();
}

class _TuklasHomeState extends State<TuklasHome> {
  int _selectedIndex = 0;

  void _selectTab(int index) => setState(() => _selectedIndex = index);

  @override
  Widget build(BuildContext context) {
    final pages = [
      _HomePage(
        onOpenPathways: () => _selectTab(1),
        onOpenScan: () => _selectTab(2),
      ),
      const _PathwaysPage(),
      const _ScanPage(),
    ];

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 20,
        title: const Text(
          'tuklas',
          style: TextStyle(
            color: _ink,
            fontSize: 25,
            fontWeight: FontWeight.w600,
            letterSpacing: -1.2,
          ),
        ),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 20),
            child: Center(
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
                decoration: BoxDecoration(
                  color: _gray,
                  border: Border.all(color: _line),
                  borderRadius: BorderRadius.circular(99),
                ),
                child: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.location_on_outlined, size: 14, color: _violet),
                    SizedBox(width: 4),
                    Text(
                      'Bugallon',
                      style: TextStyle(
                        color: _ink,
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
      body: AnimatedSwitcher(
        duration: const Duration(milliseconds: 220),
        switchInCurve: Curves.easeOut,
        child: KeyedSubtree(
          key: ValueKey(_selectedIndex),
          child: pages[_selectedIndex],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _selectedIndex,
        onDestinationSelected: _selectTab,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home_rounded),
            label: 'Home',
          ),
          NavigationDestination(
            icon: Icon(Icons.route_outlined),
            selectedIcon: Icon(Icons.route_rounded),
            label: 'Pathways',
          ),
          NavigationDestination(
            icon: Icon(Icons.document_scanner_outlined),
            selectedIcon: Icon(Icons.document_scanner),
            label: 'AI Scan',
          ),
        ],
      ),
    );
  }
}

class _HomePage extends StatelessWidget {
  const _HomePage({required this.onOpenPathways, required this.onOpenScan});

  final VoidCallback onOpenPathways;
  final VoidCallback onOpenScan;

  @override
  Widget build(BuildContext context) {
    return ListView(
      key: const PageStorageKey('home'),
      padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
          decoration: BoxDecoration(
            color: Colors.white,
            border: Border.all(color: _line),
            borderRadius: BorderRadius.circular(99),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.auto_awesome, color: _violet, size: 15),
              SizedBox(width: 6),
              Text(
                'Powered by Google Gemini AI',
                style: TextStyle(color: _ink, fontSize: 11),
              ),
            ],
          ),
        ),
        const SizedBox(height: 15),
        const Text(
          'AI-powered career path and skills development for youth in Pangasinan',
          style: TextStyle(
            color: _ink,
            fontSize: 29,
            height: 1.1,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 9),
        const Text(
          'Piloting in Bugallon, with TESDA Lingayen trainings.',
          style: TextStyle(color: _muted, fontSize: 14, height: 1.45),
        ),
        const SizedBox(height: 20),
        _BrandArtwork(onTap: onOpenPathways),
        const SizedBox(height: 29),
        const _SectionHeading(
          eyebrow: 'A path that starts with you',
          title: 'Guidance built around you',
        ),
        const SizedBox(height: 11),
        const Text(
          'Your interests, skills, schooling, credentials, and livelihood interests can help shape future guidance.',
          style: TextStyle(color: _muted, fontSize: 14, height: 1.55),
        ),
        const SizedBox(height: 16),
        const _InterestStrip(),
        const SizedBox(height: 8),
        const Text(
          'Example interests from the Tuklas preview. Recommendations are not yet available in the app.',
          style: TextStyle(color: _muted, fontSize: 11, height: 1.4),
        ),
        const SizedBox(height: 29),
        const _SectionHeading(
          eyebrow: 'Explore Tuklas',
          title: 'Your next steps',
        ),
        const SizedBox(height: 8),
        _FeatureRow(
          number: '01',
          title: 'Tell us about you',
          detail: 'Profile details help shape relevant guidance.',
          color: _yellow,
          onTap: onOpenPathways,
        ),
        _FeatureRow(
          number: '02',
          title: 'Scan a resume or certificate',
          detail: 'Optional Gemini scan with your consent.',
          color: _pink,
          onTap: onOpenScan,
        ),
        _FeatureRow(
          number: '03',
          title: 'Explore career and training guidance',
          detail: 'Assessment and recommendations are coming soon.',
          color: _violet,
          onTap: onOpenPathways,
        ),
        const SizedBox(height: 17),
        const _GuidanceNotice(),
      ],
    );
  }
}

class _BrandArtwork extends StatelessWidget {
  const _BrandArtwork({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 268,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Positioned.fill(
            top: 9,
            left: 7,
            right: -2,
            child: Transform.rotate(
              angle: -.035,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: _yellow,
                  borderRadius: BorderRadius.circular(24),
                ),
              ),
            ),
          ),
          Positioned.fill(
            top: 4,
            left: 3,
            right: 2,
            child: Transform.rotate(
              angle: -.018,
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: _pink,
                  borderRadius: BorderRadius.circular(24),
                ),
              ),
            ),
          ),
          Positioned.fill(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: Stack(
                children: [
                  const Positioned.fill(
                    child: CustomPaint(painter: _PathArtworkPainter()),
                  ),
                  Positioned(
                    left: 20,
                    right: 72,
                    bottom: 20,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'YOUR FUTURE,\nMORE THAN ONE PATH.',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 21,
                            height: 1.12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 13),
                        FilledButton.tonalIcon(
                          onPressed: onTap,
                          style: FilledButton.styleFrom(
                            backgroundColor: Colors.white,
                            foregroundColor: _deep,
                            minimumSize: const Size(0, 40),
                          ),
                          icon: const Icon(Icons.arrow_forward, size: 16),
                          label: const Text('See how it works'),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PathArtworkPainter extends CustomPainter {
  const _PathArtworkPainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawColor(_deep, BlendMode.src);
    final center = Offset(size.width * .76, size.height * .32);
    final ringPaint = Paint()
      ..color = Colors.white.withValues(alpha: .2)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    for (final radius in [34.0, 59.0, 85.0]) {
      canvas.drawCircle(center, radius, ringPaint);
    }

    final routePaint = Paint()
      ..color = Colors.white.withValues(alpha: .32)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1.5;
    final route = Path()
      ..moveTo(size.width * .43, size.height * .3)
      ..cubicTo(
        size.width * .58,
        size.height * .13,
        size.width * .7,
        size.height * .56,
        size.width * .94,
        size.height * .24,
      );
    canvas.drawPath(route, routePaint);

    final pin = Path()
      ..moveTo(center.dx, center.dy + 35)
      ..cubicTo(
        center.dx - 38,
        center.dy - 9,
        center.dx - 21,
        center.dy - 43,
        center.dx,
        center.dy - 43,
      )
      ..cubicTo(
        center.dx + 23,
        center.dy - 43,
        center.dx + 39,
        center.dy - 9,
        center.dx,
        center.dy + 35,
      )
      ..close();
    canvas.drawShadow(pin, Colors.black.withValues(alpha: .2), 5, false);
    canvas.drawPath(pin, Paint()..color = _pink);
    canvas.drawCircle(center.translate(0, -13), 8, Paint()..color = _deep);

    canvas.drawCircle(
      Offset(size.width * .43, size.height * .3),
      10,
      Paint()..color = _yellow,
    );
    canvas.drawCircle(
      Offset(size.width * .94, size.height * .24),
      5,
      Paint()..color = Colors.white,
    );
    canvas.drawCircle(
      Offset(size.width * .54, size.height * .56),
      3,
      Paint()..color = _yellow,
    );
  }

  @override
  bool shouldRepaint(covariant _PathArtworkPainter oldDelegate) => false;
}

class _SectionHeading extends StatelessWidget {
  const _SectionHeading({required this.eyebrow, required this.title});

  final String eyebrow;
  final String title;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          eyebrow.toUpperCase(),
          style: const TextStyle(
            color: _violet,
            fontSize: 10,
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 5),
        Text(
          title,
          style: const TextStyle(
            color: _ink,
            fontSize: 22,
            height: 1.2,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}

class _InterestStrip extends StatelessWidget {
  const _InterestStrip();

  @override
  Widget build(BuildContext context) {
    const interests = [
      'Electronics',
      'Farming',
      'Cooking',
      'Computers',
      'Sewing',
    ];
    const colors = [_yellow, _pink, Color(0xFFE7E5FF)];

    return Wrap(
      spacing: 7,
      runSpacing: 7,
      children: [
        for (var index = 0; index < interests.length; index++)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 7),
            decoration: BoxDecoration(
              color: colors[index % colors.length],
              borderRadius: BorderRadius.circular(99),
            ),
            child: Text(
              interests[index],
              style: const TextStyle(
                color: _ink,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
      ],
    );
  }
}

class _FeatureRow extends StatelessWidget {
  const _FeatureRow({
    required this.number,
    required this.title,
    required this.detail,
    required this.color,
    required this.onTap,
  });

  final String number;
  final String title;
  final String detail;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: const EdgeInsets.symmetric(vertical: 2),
      leading: Container(
        width: 38,
        height: 38,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: color,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          number,
          style: TextStyle(
            color: color == _violet ? Colors.white : _ink,
            fontSize: 12,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      title: Text(
        title,
        style: const TextStyle(
          color: _ink,
          fontSize: 14,
          fontWeight: FontWeight.w600,
        ),
      ),
      subtitle: Text(
        detail,
        style: const TextStyle(color: _muted, fontSize: 12, height: 1.4),
      ),
      trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: _muted),
      onTap: onTap,
    );
  }
}

class _GuidanceNotice extends StatelessWidget {
  const _GuidanceNotice();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _gray,
        border: Border.all(color: _line),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, color: _violet, size: 18),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Tuklas offers guidance to help you decide. Suggestions are not guarantees of a job, admission, or a training slot.',
              style: TextStyle(color: _muted, fontSize: 12, height: 1.5),
            ),
          ),
        ],
      ),
    );
  }
}

class _PathwaysPage extends StatelessWidget {
  const _PathwaysPage();

  @override
  Widget build(BuildContext context) {
    return ListView(
      key: const PageStorageKey('pathways'),
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 32),
      children: [
        const _SectionHeading(
          eyebrow: 'How Tuklas works',
          title: 'A clearer next step, at your pace.',
        ),
        const SizedBox(height: 21),
        const _PathwayStep(
          number: '01',
          title: 'Tell us about you',
          description: 'Share your schooling, interests, skills, credentials, and livelihood interests. Update them when things change.',
          status: 'Profile',
          accent: _orange,
        ),
        const _PathwayStep(
          number: '02',
          title: 'Take the skills assessment',
          description: 'A short assessment is planned to help make future guidance more relevant.',
          status: 'Coming soon',
          accent: _yellow,
        ),
        const _PathwayStep(
          number: '03',
          title: 'Explore careers and training',
          description: 'Future suggestions will be based on your profile and published TESDA Lingayen program information.',
          status: 'Coming soon',
          accent: _violet,
        ),
        const SizedBox(height: 10),
        const _GuidanceNotice(),
      ],
    );
  }
}

class _PathwayStep extends StatelessWidget {
  const _PathwayStep({
    required this.number,
    required this.title,
    required this.description,
    required this.status,
    required this.accent,
  });

  final String number;
  final String title;
  final String description;
  final String status;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _gray,
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 38,
            height: 38,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: accent,
              borderRadius: BorderRadius.circular(11),
            ),
            child: Text(
              number,
              style: TextStyle(
                color: accent == _violet ? Colors.white : _ink,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          const SizedBox(width: 13),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: _ink,
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 5),
                Text(
                  description,
                  style: const TextStyle(
                    color: _muted,
                    fontSize: 12,
                    height: 1.5,
                  ),
                ),
                const SizedBox(height: 9),
                Text(
                  status,
                  style: TextStyle(
                    color: status == 'Coming soon' ? _muted : _violet,
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _ScanPage extends StatelessWidget {
  const _ScanPage();

  @override
  Widget build(BuildContext context) {
    return ListView(
      key: const PageStorageKey('scan'),
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 32),
      children: [
        const Row(
          children: [
            Icon(Icons.auto_awesome, color: _violet, size: 18),
            SizedBox(width: 7),
            Text(
              'GOOGLE GEMINI AI',
              style: TextStyle(
                color: _violet,
                fontSize: 10,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        const Text(
          'Make your experience easier to show.',
          style: TextStyle(
            color: _ink,
            fontSize: 27,
            height: 1.12,
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 9),
        const Text(
          'A resume or certificate scan can summarize qualifications and suggest practical next steps.',
          style: TextStyle(color: _muted, fontSize: 14, height: 1.55),
        ),
        const SizedBox(height: 20),
        Container(
          padding: const EdgeInsets.all(17),
          decoration: BoxDecoration(
            color: _gray,
            border: Border.all(color: _line),
            borderRadius: BorderRadius.circular(15),
          ),
          child: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(Icons.upload_file_outlined, color: _violet),
                  SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Resume or certificate',
                      style: TextStyle(
                        color: _ink,
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: 8),
              Text(
                'PDF or image · up to 10 MB · one document at a time',
                style: TextStyle(color: _muted, fontSize: 12, height: 1.4),
              ),
              SizedBox(height: 15),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Icon(Icons.lock_outline, color: _pink, size: 17),
                  SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Scanning is optional. A scan sends a copy to Google Gemini. Review and confirm any suggested details before they are saved.',
                      style: TextStyle(
                        color: _muted,
                        fontSize: 12,
                        height: 1.5,
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        Container(
          padding: const EdgeInsets.all(13),
          decoration: BoxDecoration(
            color: const Color(0xFFFFF8D9),
            borderRadius: BorderRadius.circular(11),
          ),
          child: const Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(Icons.info_outline, color: _ink, size: 17),
              SizedBox(width: 9),
              Expanded(
                child: Text(
                  'Mobile scanning is not connected yet. The app will not upload documents until its secure API flow is available.',
                  style: TextStyle(color: _ink, fontSize: 12, height: 1.45),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

const _orange = Color(0xFFF47C33);
