class AppConfig {
  // Override the Android emulator default for a physical device, iOS simulator,
  // or deployed API:
  //   flutter run --dart-define=API_BASE=http://127.0.0.1:8000/api/mobile
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'http://10.0.2.2:8000/api/mobile',
  );
}
