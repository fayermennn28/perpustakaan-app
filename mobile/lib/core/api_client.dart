import 'package:dio/dio.dart';

import 'config.dart';

/// Satu instance Dio untuk seluruh aplikasi.
/// Otomatis menempelkan token ke setiap request.
class ApiClient {
  ApiClient._() {
    dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 15),
        headers: {'Accept': 'application/json'},
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          final token = _token;
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) {
          // Token ditolak server (kedaluwarsa, dicabut, akun dinonaktifkan)
          if (error.response?.statusCode == 401 && _token != null) {
            onUnauthorized?.call();
          }
          handler.next(error);
        },
      ),
    );
  }

  static final ApiClient instance = ApiClient._();

  late final Dio dio;
  String? _token;

  /// Dipanggil saat server membalas 401 (sesi tidak valid lagi).
  void Function()? onUnauthorized;

  void setToken(String? token) => _token = token;
}

/// Mengubah error apa pun menjadi pesan yang ramah untuk pengguna.
String apiErrorMessage(Object error) {
  if (error is DioException) {
    final data = error.response?.data;

    if (data is Map) {
      // Error validasi Laravel: {"message": "...", "errors": {"field": ["pesan"]}}
      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return first.first.toString();
      }
      if (data['message'] is String) return data['message'] as String;
    }

    switch (error.type) {
      case DioExceptionType.connectionTimeout:
      case DioExceptionType.sendTimeout:
      case DioExceptionType.receiveTimeout:
        return 'Koneksi ke server terlalu lama. Coba lagi.';
      case DioExceptionType.connectionError:
        return 'Tidak dapat terhubung ke server. Periksa koneksi dan pastikan server menyala.';
      default:
        return 'Terjadi kesalahan jaringan.';
    }
  }
  return 'Terjadi kesalahan tak terduga.';
}
