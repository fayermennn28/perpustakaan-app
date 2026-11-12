import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../core/api_client.dart';
import '../models/user.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthProvider extends ChangeNotifier {
  AuthProvider() {
    _api.onUnauthorized = _handleUnauthorized;
  }

  static const _tokenKey = 'auth_token';

  final _storage = const FlutterSecureStorage();
  final _api = ApiClient.instance;

  AuthStatus status = AuthStatus.unknown;
  User? user;

  /// Dipanggil sekali saat aplikasi dibuka: cek token tersimpan.
  Future<void> bootstrap() async {
    final token = await _storage.read(key: _tokenKey);

    if (token == null) {
      status = AuthStatus.unauthenticated;
      notifyListeners();
      return;
    }

    _api.setToken(token);

    try {
      final res = await _api.dio.get('/me');
      user = User.fromJson(res.data['user'] as Map<String, dynamic>);
      status = AuthStatus.authenticated;
    } on DioException catch (e) {
      if (e.response?.statusCode == 401) {
        await _storage.delete(key: _tokenKey);
      }
      _api.setToken(null);
      status = AuthStatus.unauthenticated;
    }

    notifyListeners();
  }

  /// Mengembalikan null jika berhasil, atau pesan error jika gagal.
  Future<String?> login(String email, String password) async {
    try {
      final res = await _api.dio.post(
        '/login',
        data: {'email': email, 'password': password},
      );

      final token = res.data['token'] as String;
      await _storage.write(key: _tokenKey, value: token);
      _api.setToken(token);

      user = User.fromJson(res.data['user'] as Map<String, dynamic>);
      status = AuthStatus.authenticated;
      notifyListeners();
      return null;
    } catch (e) {
      return apiErrorMessage(e);
    }
  }

  Future<void> logout() async {
    try {
      await _api.dio.post('/logout');
    } catch (_) {
      // Tetap lanjut keluar walau server tidak terjangkau
    }
    await _clearSession();
  }

  Future<void> _clearSession() async {
    await _storage.delete(key: _tokenKey);
    _api.setToken(null);
    user = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  void _handleUnauthorized() {
    if (status == AuthStatus.authenticated) {
      _clearSession();
    }
  }

  /// Ambil ulang data pengguna (foto, nama, dll.) dari server.
  Future<void> refreshUser() async {
    if (status != AuthStatus.authenticated) return;

    try {
      final res = await _api.dio.get('/me');
      user = User.fromJson(res.data['user'] as Map<String, dynamic>);
      notifyListeners();
    } catch (_) {
      // Tetap pakai data yang ada
    }
  }
}
