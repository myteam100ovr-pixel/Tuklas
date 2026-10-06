class AppConfig {
  // Override this production default for local development:
  //   flutter run --dart-define=API_BASE=http://10.0.2.2:8000/api/mobile
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'https://tuklasprojectt.up.railway.app/api/mobile',
  );
}
