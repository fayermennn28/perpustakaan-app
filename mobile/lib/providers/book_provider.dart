import 'package:flutter/foundation.dart';

import '../core/api_client.dart';
import '../models/book.dart';

class BookProvider extends ChangeNotifier {
  final _api = ApiClient.instance;

  final List<Book> books = [];
  List<BookCategory> categories = [];

  bool loading = false; // memuat halaman pertama
  bool loadingMore = false; // memuat halaman berikutnya
  bool loadMoreFailed = false;
  String? error;

  String search = '';
  int? categoryId;

  int _page = 1;
  int _lastPage = 1;
  int _requestId = 0; // untuk mengabaikan respons lama
  bool _disposed = false;

  bool get hasMore => _page < _lastPage;

  Future<void> init() async {
    await Future.wait([loadCategories(), refresh()]);
  }

  Future<void> loadCategories() async {
    try {
      final res = await _api.dio.get('/categories');
      categories = (res.data as List)
          .map((e) => BookCategory.fromJson(e as Map<String, dynamic>))
          .toList();
      notifyListeners();
    } catch (_) {
      // Kategori hanya pelengkap filter, error diabaikan
    }
  }

  /// Muat ulang dari halaman pertama (dipakai juga untuk tarik-segarkan).
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

      books
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

      books.addAll(result.items);
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

  void setSearch(String value) {
    if (value == search) return;
    search = value;
    refresh();
  }

  void setCategory(int? id) {
    if (id == categoryId) return;
    categoryId = id;
    refresh();
  }

  Future<({List<Book> items, int lastPage})> _fetchPage(int page) async {
    final res = await _api.dio.get(
      '/books',
      queryParameters: {
        'page': page,
        if (search.isNotEmpty) 'search': search,
        if (categoryId != null) 'category_id': categoryId,
      },
    );

    final data = res.data as Map<String, dynamic>;
    final items = (data['data'] as List)
        .map((e) => Book.fromJson(e as Map<String, dynamic>))
        .toList();
    final lastPage = (data['meta']?['last_page'] as int?) ?? 1;

    return (items: items, lastPage: lastPage);
  }

  // Cegah error "dipakai setelah dispose" jika pengguna logout saat request berjalan
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
