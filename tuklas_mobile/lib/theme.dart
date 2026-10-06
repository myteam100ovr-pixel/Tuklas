import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// The same colours as the website (resources/css/theme.css).
class TkColors {
  const TkColors({
    required this.isDark,
    required this.bg,
    required this.surface,
    required this.surface2,
    required this.ink,
    required this.ink2,
    required this.muted,
    required this.line,
    required this.good,
    required this.goodBg,
    required this.warn,
    required this.warnBg,
    required this.bad,
    required this.badBg,
    required this.taskMeta,
  });

  final bool isDark;
  final Color bg;
  final Color surface;
  final Color surface2;
  final Color ink;
  final Color ink2;
  final Color muted;
  final Color line;
  final Color good;
  final Color goodBg;
  final Color warn;
  final Color warnBg;
  final Color bad;
  final Color badBg;
  final Color taskMeta;

  static const violet = Color(0xFF5B17FF);
  static const pink = Color(0xFFF55BA8);
  static const yellow = Color(0xFFFFD92E);
  static const orange = Color(0xFFF47C33);

  static const light = TkColors(
    isDark: false,
    bg: Color(0xFFFFFFFF),
    surface: Color(0xFFFFFFFF),
    surface2: Color(0xFFF6F6F8),
    ink: Color(0xFF0D0D0F),
    ink2: Color(0xFF3A3A44),
    muted: Color(0xFF7A7A86),
    line: Color(0xFFE7E7EC),
    good: Color(0xFF12805C),
    goodBg: Color(0xFFDFF5EC),
    warn: Color(0xFF8A5A00),
    warnBg: Color(0xFFFFF1C9),
    bad: Color(0xFFC62828),
    badBg: Color(0xFFFDE3E3),
    taskMeta: Color(0xFF5B17FF),
  );

  static const dark = TkColors(
    isDark: true,
    bg: Color(0xFF0C0B12),
    surface: Color(0xFF15141D),
    surface2: Color(0xFF1D1C28),
    ink: Color(0xFFF4F3FA),
    ink2: Color(0xFFD4D2E2),
    muted: Color(0xFF9B98AD),
    line: Color(0xFF2A2838),
    good: Color(0xFF5FD6A8),
    goodBg: Color(0xFF123A2E),
    warn: Color(0xFFF2C35B),
    warnBg: Color(0xFF3B3012),
    bad: Color(0xFFFF8D8D),
    badBg: Color(0xFF411C1C),
    taskMeta: Color(0xFFB9A3FF),
  );

  static TkColors of(BuildContext context) =>
      Theme.of(context).brightness == Brightness.dark ? dark : light;
}

/// Text in the website's font (Schibsted Grotesk). `weight` is 400 or 500.
TextStyle tkText(
  BuildContext context, {
  double size = 14,
  int weight = 400,
  Color? color,
  double spacing = 0,
  double? height,
  bool tabular = false,
}) {
  return TextStyle(
    fontFamily: 'SchibstedGrotesk',
    fontSize: size,
    fontWeight: weight >= 500 ? FontWeight.w500 : FontWeight.w400,
    fontVariations: [FontVariation('wght', weight.toDouble())],
    fontFeatures: tabular ? const [FontFeature.tabularFigures()] : null,
    color: color ?? TkColors.of(context).ink,
    letterSpacing: spacing,
    height: height,
  );
}

ThemeData tkTheme(Brightness brightness) {
  final c = brightness == Brightness.dark ? TkColors.dark : TkColors.light;
  final base = ColorScheme.fromSeed(
    seedColor: TkColors.violet,
    brightness: brightness,
  );

  OutlineInputBorder border(Color color, [double width = 1]) =>
      OutlineInputBorder(
        borderRadius: BorderRadius.circular(13),
        borderSide: BorderSide(color: color, width: width),
      );

  return ThemeData(
    useMaterial3: true,
    brightness: brightness,
    fontFamily: 'SchibstedGrotesk',
    scaffoldBackgroundColor: c.bg,
    dividerColor: c.line,
    colorScheme: base.copyWith(
      primary: TkColors.violet,
      onPrimary: Colors.white,
      surface: c.surface,
      onSurface: c.ink,
      error: c.bad,
      outline: c.line,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: c.surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: border(c.line),
      enabledBorder: border(c.line),
      focusedBorder: border(TkColors.violet, 1.5),
      errorBorder: border(c.bad),
      focusedErrorBorder: border(c.bad, 1.5),
      errorStyle: TextStyle(color: c.bad, fontSize: 12.5),
      hintStyle: TextStyle(color: c.muted),
    ),
    popupMenuTheme: PopupMenuThemeData(
      color: c.surface,
      elevation: 10,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(18),
        side: BorderSide(color: c.line),
      ),
    ),
  );
}

/// Light / dark choice, saved on the phone. Until one is picked, the phone's own setting is used.
class ThemeController extends ChangeNotifier {
  static const _key = 'tuklas-theme';
  ThemeMode _mode = ThemeMode.system;

  ThemeMode get mode => _mode;

  Future<void> load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final saved = prefs.getString(_key);
      _mode = saved == 'dark'
          ? ThemeMode.dark
          : saved == 'light'
          ? ThemeMode.light
          : ThemeMode.system;
      notifyListeners();
    } on PlatformException catch (error, stackTrace) {
      debugPrint(
        'Unable to load the saved theme preference: $error\n$stackTrace',
      );
    } on MissingPluginException catch (error, stackTrace) {
      debugPrint('Theme preferences are unavailable: $error\n$stackTrace');
    }
  }

  Future<void> toggle(BuildContext context) async {
    final wasDark = Theme.of(context).brightness == Brightness.dark;
    _mode = wasDark ? ThemeMode.light : ThemeMode.dark;
    notifyListeners();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, wasDark ? 'light' : 'dark');
  }
}

final themeController = ThemeController();
