import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/navigation.dart';
import '../models/book.dart';
import '../providers/book_provider.dart';
import '../widgets/book_cover.dart';
import '../widgets/glass.dart';

class BooksScreen extends StatefulWidget {
  const BooksScreen({super.key});

  @override
  State<BooksScreen> createState() => _BooksScreenState();
}

class _BooksScreenState extends State<BooksScreen> {
  final _scroll = ScrollController();
  final _searchController = TextEditingController();
  Timer? _debounce;

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _scroll.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 300) {
      context.read<BookProvider>().loadMore();
    }
  }

  void _onSearchChanged(String value) {
    setState(() {}); // memperbarui tombol hapus (X)
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 450), () {
      if (mounted) context.read<BookProvider>().setSearch(value.trim());
    });
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<BookProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Katalog Buku')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
            child: TextField(
              controller: _searchController,
              onChanged: _onSearchChanged,
              textInputAction: TextInputAction.search,
              decoration: glassInputDecoration(
                context,
                hint: 'Cari judul atau penulis',
                prefix: Icons.search_rounded,
                radius: 28,
                suffix: _searchController.text.isEmpty
                    ? null
                    : IconButton(
                        icon: const Icon(Icons.close_rounded),
                        onPressed: () {
                          _searchController.clear();
                          _onSearchChanged('');
                        },
                      ),
              ),
            ),
          ),
          SizedBox(
            height: 48,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(16, 4, 16, 8),
              children: [
                _chip('Semua', p.categoryId == null, () => p.setCategory(null)),
                for (final c in p.categories)
                  _chip(
                    c.name,
                    p.categoryId == c.id,
                    () => p.setCategory(c.id),
                  ),
              ],
            ),
          ),
          Expanded(child: _buildBody(p)),
        ],
      ),
    );
  }

  Widget _chip(String label, bool selected, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: GlassChip(label: label, selected: selected, onTap: onTap),
    );
  }

  Widget _buildBody(BookProvider p) {
    if (p.loading && p.books.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    return RefreshIndicator(
      onRefresh: p.refresh,
      child: LayoutBuilder(
        builder: (context, constraints) {
          if (p.error != null && p.books.isEmpty) {
            return _scrollableMessage(
              icon: Icons.cloud_off_rounded,
              text: p.error!,
              actionLabel: 'Coba lagi',
              onAction: p.refresh,
            );
          }

          if (p.books.isEmpty) {
            return _scrollableMessage(
              icon: Icons.search_off_rounded,
              text: 'Buku tidak ditemukan.',
            );
          }

          final columns = (constraints.maxWidth / 180).floor().clamp(2, 5);

          return CustomScrollView(
            controller: _scroll,
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 4, 16, 0),
                sliver: SliverGrid(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) => _BookCard(book: p.books[index]),
                    childCount: p.books.length,
                  ),
                  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: columns,
                    mainAxisSpacing: 14,
                    crossAxisSpacing: 14,
                    childAspectRatio: 0.62,
                  ),
                ),
              ),
              SliverToBoxAdapter(child: _footer(p)),
            ],
          );
        },
      ),
    );
  }

  Widget _footer(BookProvider p) {
    if (p.loadingMore) {
      return const Padding(
        padding: EdgeInsets.all(24),
        child: Center(
          child: SizedBox(
            width: 24,
            height: 24,
            child: CircularProgressIndicator(strokeWidth: 2.5),
          ),
        ),
      );
    }

    if (p.loadMoreFailed) {
      return Padding(
        padding: const EdgeInsets.all(16),
        child: Center(
          child: TextButton.icon(
            onPressed: () => p.loadMore(retry: true),
            icon: const Icon(Icons.refresh),
            label: const Text('Gagal memuat, ketuk untuk coba lagi'),
          ),
        ),
      );
    }

    return const SizedBox(height: 24);
  }

  Widget _scrollableMessage({
    required IconData icon,
    required String text,
    String? actionLabel,
    Future<void> Function()? onAction,
  }) {
    final scheme = Theme.of(context).colorScheme;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      children: [
        const SizedBox(height: 48),
        Icon(icon, size: 64, color: scheme.outline),
        const SizedBox(height: 16),
        Text(
          text,
          textAlign: TextAlign.center,
          style: TextStyle(color: scheme.onSurfaceVariant),
        ),
        if (actionLabel != null && onAction != null) ...[
          const SizedBox(height: 16),
          Center(
            child: FilledButton.tonal(
              onPressed: onAction,
              child: Text(actionLabel),
            ),
          ),
        ],
      ],
    );
  }
}

class _BookCard extends StatelessWidget {
  const _BookCard({required this.book});

  final Book book;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return GlassCard(
      padding: EdgeInsets.zero,
      radius: 20,
      onTap: () => openBookDetail(context, book),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Stack(
              fit: StackFit.expand,
              children: [
                Hero(
                  tag: 'cover-${book.id}',
                  child: BookCover(url: book.coverUrl),
                ),
                Positioned(
                  top: 8,
                  left: 8,
                  child: _StockBadge(available: book.available),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  book.title,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  book.author,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 12, color: muted),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _StockBadge extends StatelessWidget {
  const _StockBadge({required this.available});

  final bool available;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: available
            ? const Color(0xFF059669)
            : Theme.of(context).colorScheme.error,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        available ? 'Tersedia' : 'Habis',
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w700,
        ),
      ),
    );
  }
}
