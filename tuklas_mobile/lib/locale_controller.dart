import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class LocaleController extends ChangeNotifier {
  static const preferenceKey = 'tuklas_locale';

  Locale _locale = const Locale('en');

  Locale get locale => _locale;
  String get languageCode => _locale.languageCode;

  Future<void> load() async {
    try {
      final preferences = await SharedPreferences.getInstance();
      _setLocale(preferences.getString(preferenceKey));
    } catch (_) {
      // English remains available when local preferences cannot be read.
    }
  }

  Future<void> setLocale(String languageCode) async {
    final normalized = _normalize(languageCode);
    if (normalized == null) return;
    if (_locale.languageCode != normalized) {
      _locale = Locale(normalized);
      notifyListeners();
    }
    try {
      final preferences = await SharedPreferences.getInstance();
      await preferences.setString(preferenceKey, normalized);
    } catch (_) {
      // The active locale still changes for this run if storage is unavailable.
    }
  }

  Future<void> hydrateFromUser(dynamic value) async {
    final normalized = _normalize(value);
    if (normalized != null) {
      await setLocale(normalized);
    }
  }

  void _setLocale(String? value) {
    final normalized = _normalize(value);
    if (normalized != null && _locale.languageCode != normalized) {
      _locale = Locale(normalized);
      notifyListeners();
    }
  }

  String? _normalize(dynamic value) {
    if (value is! String) return null;
    final languageCode = value.toLowerCase().split(RegExp('[-_]')).first;
    return languageCode == 'en' || languageCode == 'tl' ? languageCode : null;
  }
}

final localeController = LocaleController();
