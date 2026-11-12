import 'dart:async';

import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../models/loan.dart';

class LoanProvider extends ChangeNotifier {
  final _api = ApiClient.instance;

  final List<Loan> loans = [];

  /// null = semua status
  String? status;

  bool loading = false;
  bool loadingMore = false;
  bool loadMoreFailed = false;
  String? error;

  int _page = 1;
  int _lastPage = 1;
  int _requestId = 0;
  bool _disposed = false;

  bool get hasMore => _page < _lastPage;

  Future<void> refresh() async {
    final id = ++_requestId;

    loading = true;
    loadingMore = false;
    loadMoreFailed = false;
    error = null;
    notifyListeners();

    try {
      final result = await _fetchPage(1);
      if (id != _requestId) return;

      loans
        ..clear()
        ..addAll(result.items);
      _page = 1;
      _lastPage = result.lastPage;
    } catch (e) {
      if (id != _requestId) return;
      error = apiErrorMessage(e);
    }

    loading = false;
    notifyListeners();
  }

  Future<void> loadMore({bool retry = false}) async {
    if (loading || loadingMore || !hasMore) return;
    if (loadMoreFailed && !retry) return;

    final id = _requestId;
    loadingMore = true;
    loadMoreFailed = false;
    notifyListeners();

    try {
      final result = await _fetchPage(_page + 1);
      if (id != _requestId) return;

      loans.addAll(result.items);
      _page += 1;
      _lastPage = result.lastPage;
    } catch (_) {
      if (id == _requestId) loadMoreFailed = true;
    } finally {
      if (id == _requestId) {
        loadingMore = false;
        notifyListeners();
      }
    }
  }

  void setStatus(String? value) {
    if (value == status) return;
    status = value;
    refresh();
  }

  /// Mengembalikan null jika berhasil, atau pesan error dari server.
  Future<String?> requestLoan(int bookId) async {
    try {
      await _api.dio.post('/loans', data: {'book_id': bookId});
      unawaited(refresh()); // pengajuan baru langsung muncul di tab Pinjaman
      return null;
    } catch (e) {
      return apiErrorMessage(e);
    }
  }

  /// Mengembalikan null jika berhasil, atau pesan error dari server.
  Future<String?> requestReturn(int loanId) async {
    try {
      await _api.dio.post('/loans/$loanId/return');
      await refresh();
      return null;
    } catch (e) {
      return apiErrorMessage(e);
    }
  }

  Future<({List<Loan> items, int lastPage})> _fetchPage(int page) async {
    final res = await _api.dio.get(
      '/loans',
      queryParameters: {'page': page, if (status != null) 'status': status},
    );

    final data = res.data as Map<String, dynamic>;
    final items = (data['data'] as List)
        .map((e) => Loan.fromJson(e as Map<String, dynamic>))
        .toList();
    final lastPage = (data['meta']?['last_page'] as int?) ?? 1;

    return (items: items, lastPage: lastPage);
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
