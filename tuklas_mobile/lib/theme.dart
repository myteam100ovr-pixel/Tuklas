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
    bg: Color(0xFFF8F7FC),
    surface: Color(0xFFFFFFFF),
    surface2: Color(0xFFF5F2FB),
    ink: Color(0xFF0D0D0F),
    ink2: Color(0xFF3A3A44),
    muted: Color(0xFF625D70),
    line: Color(0xFFE6E1EE),
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
    surface: Color(0xFF17161F),
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

ThemeData tkTheme(Brightness brightness, {bool reduceMotion = false}) {
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

  final panelShape = RoundedRectangleBorder(
    borderRadius: BorderRadius.circular(14),
    side: BorderSide(color: c.line),
  );
  final dialogShape = RoundedRectangleBorder(
    borderRadius: BorderRadius.circular(20),
    side: BorderSide(color: c.line),
  );
  final selectedColor = c.isDark ? const Color(0xFFA786FF) : TkColors.violet;
  final checkboxFill = WidgetStateProperty.resolveWith<Color>((states) {
    if (states.contains(WidgetState.disabled)) return c.line;
    if (states.contains(WidgetState.selected)) return selectedColor;
    return c.surface;
  });

  return ThemeData(
    pageTransitionsTheme: reduceMotion
        ? const PageTransitionsTheme(
            builders: {
              TargetPlatform.android: NoMotionPageTransitionsBuilder(),
              TargetPlatform.iOS: NoMotionPageTransitionsBuilder(),
            },
          )
        : null,
    useMaterial3: true,
    brightness: brightness,
    fontFamily: 'SchibstedGrotesk',
    scaffoldBackgroundColor: c.bg,
    dividerColor: c.line,
    canvasColor: c.surface,
    iconTheme: IconThemeData(color: c.ink2),
    iconButtonTheme: IconButtonThemeData(
      style: ButtonStyle(
        foregroundColor: WidgetStatePropertyAll(c.ink2),
        overlayColor: WidgetStatePropertyAll(
          selectedColor.withValues(alpha: .1),
        ),
        minimumSize: const WidgetStatePropertyAll(Size(44, 44)),
        shape: WidgetStatePropertyAll(
          RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      ),
    ),
    tooltipTheme: TooltipThemeData(
      decoration: BoxDecoration(
        color: c.ink,
        border: Border.all(color: c.line),
        borderRadius: BorderRadius.circular(8),
      ),
      textStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        color: c.bg,
        fontSize: 12,
      ),
    ),
    appBarTheme: AppBarTheme(
      backgroundColor: c.surface,
      foregroundColor: c.ink,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      centerTitle: false,
      titleTextStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 18,
        fontWeight: FontWeight.w600,
        color: c.ink,
      ),
    ),
    cardTheme: CardThemeData(
      color: c.surface,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      shape: panelShape,
    ),
    checkboxTheme: CheckboxThemeData(
      fillColor: checkboxFill,
      checkColor: const WidgetStatePropertyAll(Colors.white),
      side: BorderSide(color: c.muted, width: 1.4),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(5)),
      visualDensity: VisualDensity.standard,
    ),
    chipTheme: ChipThemeData(
      backgroundColor: c.surface2,
      disabledColor: c.surface2,
      selectedColor: selectedColor.withValues(alpha: c.isDark ? .28 : .12),
      secondarySelectedColor: selectedColor.withValues(
        alpha: c.isDark ? .28 : .12,
      ),
      checkmarkColor: selectedColor,
      showCheckmark: false,
      labelStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 12,
        color: c.ink2,
      ),
      secondaryLabelStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 12,
        fontWeight: FontWeight.w600,
        color: selectedColor,
      ),
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
      side: BorderSide(color: c.line),
      shape: const StadiumBorder(),
      iconTheme: IconThemeData(color: c.taskMeta, size: 16),
    ),
    datePickerTheme: DatePickerThemeData(
      backgroundColor: c.surface,
      surfaceTintColor: Colors.transparent,
      shape: dialogShape,
      headerBackgroundColor: selectedColor,
      headerForegroundColor: Colors.white,
      dayForegroundColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.selected)) return Colors.white;
        if (states.contains(WidgetState.disabled)) return c.muted;
        return c.ink;
      }),
      dayBackgroundColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.selected)) return selectedColor;
        return null;
      }),
      todayForegroundColor: WidgetStatePropertyAll(selectedColor),
      todayBackgroundColor: WidgetStatePropertyAll(
        selectedColor.withValues(alpha: .12),
      ),
      todayBorder: BorderSide(color: selectedColor),
      dividerColor: c.line,
    ),
    dialogTheme: DialogThemeData(
      backgroundColor: c.surface,
      surfaceTintColor: Colors.transparent,
      elevation: 12,
      shape: dialogShape,
      iconColor: c.taskMeta,
      titleTextStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 18,
        fontWeight: FontWeight.w600,
        color: c.ink,
      ),
      contentTextStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 14,
        height: 1.45,
        color: c.ink2,
      ),
      insetPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
      clipBehavior: Clip.antiAlias,
    ),
    dropdownMenuTheme: DropdownMenuThemeData(
      textStyle: TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 14,
        color: c.ink,
      ),
      menuStyle: MenuStyle(
        backgroundColor: WidgetStatePropertyAll(c.surface),
        surfaceTintColor: const WidgetStatePropertyAll(Colors.transparent),
        elevation: const WidgetStatePropertyAll(10),
        shape: WidgetStatePropertyAll(dialogShape),
        side: WidgetStatePropertyAll(BorderSide(color: c.line)),
      ),
    ),
    bottomSheetTheme: BottomSheetThemeData(
      backgroundColor: c.surface,
      modalBackgroundColor: c.surface,
      surfaceTintColor: Colors.transparent,
      showDragHandle: true,
      dragHandleColor: c.muted,
      shape: RoundedRectangleBorder(
        borderRadius: const BorderRadius.vertical(top: Radius.circular(22)),
        side: BorderSide(color: c.line),
      ),
    ),
    radioTheme: RadioThemeData(
      fillColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.disabled)) return c.muted;
        if (states.contains(WidgetState.selected)) return selectedColor;
        return c.ink2;
      }),
      visualDensity: VisualDensity.standard,
    ),
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.selected)) return Colors.white;
        return c.muted;
      }),
      trackColor: WidgetStateProperty.resolveWith<Color?>((states) {
        if (states.contains(WidgetState.selected)) return selectedColor;
        return c.surface2;
      }),
      trackOutlineColor: WidgetStatePropertyAll(c.line),
    ),
    tabBarTheme: TabBarThemeData(
      labelColor: selectedColor,
      unselectedLabelColor: c.muted,
      indicatorColor: selectedColor,
      dividerColor: c.line,
      labelStyle: const TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 13,
        fontWeight: FontWeight.w600,
      ),
      unselectedLabelStyle: const TextStyle(
        fontFamily: 'SchibstedGrotesk',
        fontSize: 13,
        fontWeight: FontWeight.w500,
      ),
    ),
    dividerTheme: DividerThemeData(color: c.line, thickness: 1, space: 1),
    progressIndicatorTheme: ProgressIndicatorThemeData(
      color: selectedColor,
      linearTrackColor: c.surface2,
      circularTrackColor: c.surface2,
    ),
    listTileTheme: ListTileThemeData(
      iconColor: c.taskMeta,
      textColor: c.ink,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 2),
    ),
    textSelectionTheme: TextSelectionThemeData(
      cursorColor: selectedColor,
      selectionColor: selectedColor.withValues(alpha: .24),
      selectionHandleColor: selectedColor,
    ),
    colorScheme: base.copyWith(
      primary: c.isDark ? const Color(0xFFA786FF) : TkColors.violet,
      onPrimary: c.isDark ? c.bg : Colors.white,
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
      focusedBorder: border(c.taskMeta, 2),
      errorBorder: border(c.bad),
      focusedErrorBorder: border(c.bad, 1.5),
      errorStyle: TextStyle(color: c.bad, fontSize: 12.5),
      hintStyle: TextStyle(color: c.muted),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size(48, 48),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        minimumSize: const Size(48, 48),
        side: BorderSide(color: c.line),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(minimumSize: const Size(48, 48)),
    ),
    snackBarTheme: SnackBarThemeData(
      behavior: SnackBarBehavior.floating,
      backgroundColor: c.ink,
      contentTextStyle: TextStyle(color: c.bg, fontFamily: 'SchibstedGrotesk'),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
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

class NoMotionPageTransitionsBuilder extends PageTransitionsBuilder {
  const NoMotionPageTransitionsBuilder();

  @override
  Widget buildTransitions<T>(
    PageRoute<T> route,
    BuildContext context,
    Animation<double> animation,
    Animation<double> secondaryAnimation,
    Widget child,
  ) => child;
}

class MotionController extends ChangeNotifier {
  static const _key = 'tuklas-reduce-motion';
  bool enabled = true;

  Future<void> load() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      enabled = prefs.getBool(_key) ?? true;
      notifyListeners();
    } on PlatformException {
      // Continue with normal transitions if device preferences are unavailable.
    } on MissingPluginException {
      // Continue with normal transitions if device preferences are unavailable.
    }
  }

  Future<void> setEnabled(bool value) async {
    enabled = value;
    notifyListeners();
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool(_key, value);
    } on PlatformException {
      // Keep the current session preference when storage is unavailable.
    } on MissingPluginException {
      // Keep the current session preference when storage is unavailable.
    }
  }
}

final motionController = MotionController();
