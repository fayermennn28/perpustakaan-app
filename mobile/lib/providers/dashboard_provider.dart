import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../models/dashboard.dart';

class DashboardProvider extends ChangeNotifier {
  final _api = ApiClient.instance;

  DashboardData? data;
  bool loading = false;
  String? error;
  bool _disposed = false;

  Future<void> refresh() async {
    loading = true;
    error = null;
    notifyListeners();

    try {
      final res = await _api.dio.get('/dashboard');
      data = DashboardData.fromJson(res.data as Map<String, dynamic>);
    } catch (e) {
      error = apiErrorMessage(e);
    }

    loading = false;
    notifyListeners();
  }

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  @override
  void notifyListeners() {
    if (!_disposed) super.notifyListeners();
  }
}
