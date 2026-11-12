import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../models/book.dart';
import '../providers/loan_provider.dart';
import '../widgets/book_cover.dart';
import '../widgets/glass.dart';

class BookDetailScreen extends StatefulWidget {
  const BookDetailScreen({super.key, required this.book});

  final Book book;

  @override
  State<BookDetailScreen> createState() => _BookDetailScreenState();
}

class _BookDetailScreenState extends State<BookDetailScreen> {
  late Book _book = widget.book;
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _refreshBook();
  }

  /// Ambil data terbaru (terutama stok) dari server.
  Future<void> _refreshBook() async {
    try {
      final res = await ApiClient.instance.dio.get('/books/${_book.id}');
      final data = res.data['data'] as Map<String, dynamic>;
      if (!mounted) return;
      setState(() => _book = Book.fromJson(data));
    } catch (_) {
      // Tetap pakai data dari daftar
    }
  }

  Future<void> _requestLoan() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Ajukan peminjaman?'),
        content: Text(
          'Kamu akan mengajukan peminjaman "${_book.title}". '
          'Pengajuan akan diproses oleh admin perpustakaan.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Ajukan'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _submitting = true);
    final error = await context.read<LoanProvider>().requestLoan(_book.id);
    if (!mounted) return;
    setState(() => _submitting = false);

    if (error == null) {
      final messenger = ScaffoldMessenger.of(context);
      Navigator.of(context).pop();
      messenger.showSnackBar(
        const SnackBar(
          behavior: SnackBarBehavior.floating,
          content: Text('Pengajuan terkirim. Menunggu persetujuan admin.'),
        ),
      );
    } else {
      await showDialog<void>(
        context: context,
        builder: (ctx) => AlertDialog(
          icon: const Icon(Icons.info_outline),
          title: const Text('Belum bisa diajukan'),
          content: Text(error),
          actions: [
            FilledButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Mengerti'),
            ),
          ],
        ),
      );
      _refreshBook();
    }
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;
    final book = _book;
    const green = Color(0xFF059669);

    return Scaffold(
      appBar: AppBar(title: const Text('Detail Buku')),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
          child: FilledButton.icon(
            onPressed: (book.available && !_submitting) ? _requestLoan : null,
            icon: _submitting
                ? SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.5,
                      color: scheme.onPrimary,
                    ),
                  )
                : const Icon(Icons.bookmark_add_outlined),
            label: Text(book.available ? 'Ajukan Peminjaman' : 'Stok Habis'),
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(54),
              shape: const StadiumBorder(),
              textStyle: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
        children: [
          Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 210),
              child: DecoratedBox(
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF032541).withValues(alpha: 0.25),
                      blurRadius: 30,
                      offset: const Offset(0, 14),
                    ),
                  ],
                ),
                child: AspectRatio(
                  aspectRatio: 3 / 4,
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: Hero(
                      tag: 'cover-${book.id}',
                      child: BookCover(url: book.coverUrl),
                    ),
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 22),
          Text(
            book.title,
            textAlign: TextAlign.center,
            style: textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            book.author,
            textAlign: TextAlign.center,
            style: textTheme.titleMedium?.copyWith(
              color: scheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 14),
          Wrap(
            alignment: WrapAlignment.center,
            spacing: 8,
            runSpacing: 8,
            children: [
              if (book.category != null)
                _Pill(
                  icon: Icons.sell_outlined,
                  text: book.category!.name,
                  color: const Color(0xFF0284C7),
                ),
              _Pill(
                icon: book.available ? Icons.check_circle_outline : Icons.block,
                text: book.available ? 'Tersedia: ${book.stock}' : 'Stok habis',
                color: book.available ? green : scheme.error,
              ),
            ],
          ),
          const SizedBox(height: 18),
          GlassCard(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
            child: Column(
              children: [
                _InfoRow(label: 'Penerbit', value: book.publisher),
                const Divider(height: 1),
                _InfoRow(label: 'Tahun terbit', value: book.year?.toString()),
                const Divider(height: 1),
                _InfoRow(label: 'ISBN', value: book.isbn),
                const Divider(height: 1),
                _InfoRow(label: 'Stok', value: '${book.stock} eksemplar'),
              ],
            ),
          ),
          if (book.description != null &&
              book.description!.trim().isNotEmpty) ...[
            const SizedBox(height: 16),
            GlassCard(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Deskripsi',
                    style: textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(book.description!, style: textTheme.bodyLarge),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.icon, required this.text, required this.color});

  final IconData icon;
  final String text;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 6),
          Text(
            text,
            style: TextStyle(
              color: color,
              fontSize: 12.5,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String? value;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(
              label,
              style: TextStyle(color: scheme.onSurfaceVariant),
            ),
          ),
          Expanded(
            child: Text(
              (value == null || value!.isEmpty) ? '-' : value!,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }
}
