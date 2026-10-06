class AppConfig {
  // Android emulator:  http://10.0.2.2:8000/api/mobile
  // iOS simulator:     http://127.0.0.1:8000/api/mobile
  // Real phone:        http://YOUR_COMPUTER_LAN_IP:8000/api/mobile
  // Override without editing this file:
  //   flutter run --dart-define=API_BASE=http://192.168.1.20:8000/api/mobile
  static const String apiBase = String.fromEnvironment(
    'API_BASE',
    defaultValue: 'http://10.0.2.2:8000/api/mobile',
  );
}
