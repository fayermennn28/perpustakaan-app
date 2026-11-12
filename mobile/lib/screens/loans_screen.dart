import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/loan_provider.dart';
import '../widgets/loan_card.dart';
import '../widgets/glass.dart';

class LoansScreen extends StatefulWidget {
  const LoansScreen({super.key});

  @override
  State<LoansScreen> createState() => _LoansScreenState();
}

class _LoansScreenState extends State<LoansScreen> {
  static const List<(String?, String)> _filters = [
    (null, 'Semua'),
    ('diajukan', 'Diajukan'),
    ('dipinjam', 'Dipinjam'),
    ('pengembalian_diajukan', 'Pengajuan kembali'),
    ('dikembalikan', 'Dikembalikan'),
    ('ditolak', 'Ditolak'),
  ];

  final _scroll = ScrollController();

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
  }

  @override
  void dispose() {
    _scroll.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scroll.position.pixels >= _scroll.position.maxScrollExtent - 300) {
      context.read<LoanProvider>().loadMore();
    }
  }

  @override
  Widget build(BuildContext context) {
    final p = context.watch<LoanProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('Pinjaman Saya')),
      body: Column(
        children: [
          SizedBox(
            height: 52,
            child: ListView(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              children: [
                for (final (value, label) in _filters)
                  Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: GlassChip(
                      label: label,
                      selected: p.status == value,
                      onTap: () => p.setStatus(value),
                    ),
                  ),
              ],
            ),
          ),
          if (p.loading && p.loans.isNotEmpty)
            const LinearProgressIndicator(minHeight: 2),
          Expanded(child: _buildBody(p)),
        ],
      ),
    );
  }

  Widget _buildBody(LoanProvider p) {
    if (p.loading && p.loans.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    return RefreshIndicator(
      onRefresh: p.refresh,
      child: Builder(
        builder: (context) {
          if (p.error != null && p.loans.isEmpty) {
            return _message(
              icon: Icons.cloud_off_rounded,
              text: p.error!,
              actionLabel: 'Coba lagi',
              onAction: p.refresh,
            );
          }

          if (p.loans.isEmpty) {
            return _message(
              icon: Icons.receipt_long_outlined,
              text: p.status == null
                  ? 'Belum ada transaksi peminjaman.\nAjukan peminjaman dari tab Buku.'
                  : 'Tidak ada transaksi dengan status ini.',
            );
          }

          return CustomScrollView(
            controller: _scroll,
            physics: const AlwaysScrollableScrollPhysics(),
            slivers: [
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                sliver: SliverList(
                  delegate: SliverChildBuilderDelegate(
                    (context, index) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: LoanCard(
                        key: ValueKey(p.loans[index].id),
                        loan: p.loans[index],
                      ),
                    ),
                    childCount: p.loans.length,
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

  Widget _footer(LoanProvider p) {
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

  Widget _message({
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
