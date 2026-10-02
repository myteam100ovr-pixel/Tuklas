import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

abstract final class TuklasColors {
  static const ink = Color(0xFF0D0D0F);
  static const deep = Color(0xFF4510B9);
  static const violet = Color(0xFF5B17FF);
  static const pink = Color(0xFFF55BA8);
  static const yellow = Color(0xFFFFD92E);
  static const orange = Color(0xFFF47C33);
  static const peri = Color(0xFF7083EA);
  static const good = Color(0xFF12805C);
  static const warn = Color(0xFF8A5A00);
  static const bad = Color(0xFFC62828);
  static const lightBg = Color(0xFFFFFFFF);
  static const lightSurface2 = Color(0xFFF6F6F8);
  static const lightMuted = Color(0xFF7A7A86);
  static const lightLine = Color(0xFFE7E7EC);
  static const darkBg = Color(0xFF0C0B12);
  static const darkSurface = Color(0xFF15141D);
  static const darkSurface2 = Color(0xFF1D1C28);
  static const darkInk = Color(0xFFF4F3FA);
  static const darkMuted = Color(0xFF9B98AD);
  static const darkLine = Color(0xFF2A2838);
}

abstract final class TuklasTheme {
  static ThemeData get light => _build(Brightness.light);
  static ThemeData get dark => _build(Brightness.dark);

  static ThemeData _build(Brightness brightness) {
    final isDark = brightness == Brightness.dark;
    final background = isDark ? TuklasColors.darkBg : TuklasColors.lightBg;
    final surface = isDark ? TuklasColors.darkSurface : Colors.white;
    final surface2 = isDark ? TuklasColors.darkSurface2 : TuklasColors.lightSurface2;
    final ink = isDark ? TuklasColors.darkInk : TuklasColors.ink;
    final muted = isDark ? TuklasColors.darkMuted : TuklasColors.lightMuted;
    final line = isDark ? TuklasColors.darkLine : TuklasColors.lightLine;
    final scheme = ColorScheme.fromSeed(
      seedColor: TuklasColors.violet,
      brightness: brightness,
    ).copyWith(
      primary: TuklasColors.violet,
      secondary: TuklasColors.pink,
      tertiary: TuklasColors.yellow,
      surface: surface,
      onSurface: ink,
      outline: line,
      error: isDark ? const Color(0xFFFF8D8D) : TuklasColors.bad,
    );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: background,
      fontFamily: 'Schibsted Grotesk',
      appBarTheme: AppBarTheme(
        backgroundColor: background,
        foregroundColor: ink,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: TextStyle(
          color: ink,
          fontFamily: 'Schibsted Grotesk',
          fontSize: 20,
          fontWeight: FontWeight.w500,
        ),
      ),
      cardTheme: CardThemeData(
        color: surface2,
        elevation: 0,
        margin: EdgeInsets.zero,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      dividerTheme: DividerThemeData(color: line, thickness: 1, space: 1),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surface,
        hintStyle: TextStyle(color: muted),
        labelStyle: TextStyle(color: muted),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: line),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: line),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: TuklasColors.violet, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: scheme.error),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: surface,
        indicatorColor: TuklasColors.pink.withValues(alpha: .16),
        labelTextStyle: WidgetStateProperty.resolveWith((states) => TextStyle(
              color: states.contains(WidgetState.selected)
                  ? TuklasColors.violet
                  : muted,
              fontSize: 11,
              fontWeight: states.contains(WidgetState.selected)
                  ? FontWeight.w700
                  : FontWeight.w500,
            )),
      ),
      snackBarTheme: SnackBarThemeData(
        backgroundColor: isDark ? surface2 : TuklasColors.ink,
        contentTextStyle: TextStyle(color: isDark ? ink : Colors.white),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }
}

class TuklasThemeController extends ChangeNotifier {
  TuklasThemeController(this._preferences)
      : _mode = switch (_preferences.getString('tuklas-theme')) {
          'dark' => ThemeMode.dark,
          'light' => ThemeMode.light,
          _ => ThemeMode.system,
        };

  final SharedPreferences _preferences;
  ThemeMode _mode;

  ThemeMode get mode => _mode;

  Future<void> setMode(ThemeMode mode) async {
    _mode = mode;
    await _preferences.setString('tuklas-theme', switch (mode) {
      ThemeMode.light => 'light',
      ThemeMode.dark => 'dark',
      ThemeMode.system => 'system',
    });
    notifyListeners();
  }

  Future<void> toggle() => setMode(_mode == ThemeMode.dark ? ThemeMode.light : ThemeMode.dark);
}
