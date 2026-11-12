class AppConfig {
  /// Alamat API Laravel.
  /// - Emulator Android : 10.0.2.2 mewakili "localhost" komputer kamu
  /// - HP fisik         : jalankan dengan --dart-define, contoh:
  ///   flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api',
  );
}
