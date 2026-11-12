import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../core/format.dart';
import '../core/navigation.dart';
import '../models/book.dart';
import '../models/dashboard.dart';
import '../models/loan.dart';
import '../providers/auth_provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/loan_provider.dart';
import '../widgets/book_cover.dart';
import '../widgets/glass.dart';
import '../widgets/loan_status.dart';
import '../widgets/user_avatar.dart';

class DashboardScreen extends StatelessWidget {
  const DashboardScreen({super.key, required this.onNavigate});

  /// Pindah tab: 0 Beranda, 1 Buku, 2 Pinjaman, 3 Profil
  final void Function(int index) onNavigate;

  @override
  Widget build(BuildContext context) {
    final p = context.watch<DashboardProvider>();

    return SafeArea(
      bottom: false,
      child: RefreshIndicator(
        onRefresh: p.refresh,
        child: _content(context, p),
      ),
    );
  }

  Widget _content(BuildContext context, DashboardProvider p) {
    final data = p.data;

    if (data == null) {
      if (p.loading) {
        return ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: const [
            SizedBox(height: 240),
            Center(child: CircularProgressIndicator()),
          ],
        );
      }

      final scheme = Theme.of(context).colorScheme;
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.all(32),
        children: [
          const SizedBox(height: 120),
          Icon(Icons.cloud_off_rounded, size: 64, color: scheme.outline),
          const SizedBox(height: 16),
          Text(
            p.error ?? 'Gagal memuat beranda.',
            textAlign: TextAlign.center,
            style: TextStyle(color: scheme.onSurfaceVariant),
          ),
          const SizedBox(height: 16),
          Center(
            child: FilledButton.tonal(
              onPressed: p.refresh,
              child: const Text('Coba lagi'),
            ),
          ),
        ],
      );
    }

    final user = context.watch<AuthProvider>().user;
    final s = data.summary;
    final textTheme = Theme.of(context).textTheme;
    final scheme = Theme.of(context).colorScheme;
    final firstName = (user?.name ?? '').split(' ').first;

    void openLoans(String? status) {
      context.read<LoanProvider>().setStatus(status);
      onNavigate(2);
    }

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
      children: [
        // Sapaan
        Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    greeting(),
                    style: textTheme.bodyMedium?.copyWith(
                      color: scheme.onSurfaceVariant,
                    ),
                  ),
                  Text(
                    firstName.isEmpty ? 'Anggota' : firstName,
                    style: textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    longDate.format(DateTime.now()),
                    style: textTheme.bodySmall?.copyWith(
                      color: scheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            ),
            if (user != null)
              GestureDetector(
                onTap: () => onNavigate(3),
                child: UserAvatar(user: user, radius: 26),
              ),
          ],
        ),
        const SizedBox(height: 16),

        // Kartu utama
        _HeroCard(summary: s),
        const SizedBox(height: 16),

        // Kartu metrik
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.0,
          children: [
            _MetricCard(
              icon: Icons.menu_book_rounded,
              color: const Color(0xFF0284C7),
              value: '${s.activeLoans}',
              label: 'Sedang dipinjam',
              note: 'Buku di tangan kamu',
              onTap: () => openLoans('dipinjam'),
            ),
            _MetricCard(
              icon: Icons.hourglass_top_rounded,
              color: const Color(0xFFD97706),
              value: '${s.pendingRequests}',
              label: 'Menunggu persetujuan',
              note: 'Pengajuan pinjam',
              onTap: () => openLoans('diajukan'),
            ),
            _MetricCard(
              icon: Icons.assignment_return_rounded,
              color: const Color(0xFF7C3AED),
              value: '${s.pendingReturns}',
              label: 'Pengajuan kembali',
              note: 'Menunggu verifikasi',
              onTap: () => openLoans('pengembalian_diajukan'),
            ),
            _MetricCard(
              icon: Icons.payments_outlined,
              color: s.estimatedFine > 0
                  ? scheme.error
                  : const Color(0xFF059669),
              value: rupiahFormat.format(s.estimatedFine),
              label: 'Denda berjalan',
              note: s.overdue > 0
                  ? '${s.overdue} buku terlambat'
                  : 'Tidak ada denda',
              onTap: () => openLoans('dipinjam'),
            ),
          ],
        ),

        // Jatuh tempo terdekat
        _SectionHeader(
          title: 'Jatuh tempo terdekat',
          action: 'Lihat semua',
          onAction: () => openLoans('dipinjam'),
        ),
        if (data.dueSoon.isEmpty)
          const _EmptyNote(
            icon: Icons.event_available_rounded,
            text: 'Tidak ada buku yang sedang dipinjam.',
          )
        else
          for (final loan in data.dueSoon)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _LoanTile(loan: loan, onTap: () => openLoans('dipinjam')),
            ),

        // Buku populer
        if (data.popularBooks.isNotEmpty) ...[
          _SectionHeader(
            title: 'Buku populer',
            action: 'Semua buku',
            onAction: () => onNavigate(1),
          ),
          _BookStrip(books: data.popularBooks),
        ],

        // Buku terbaru
        _SectionHeader(
          title: 'Buku terbaru',
          action: 'Semua buku',
          onAction: () => onNavigate(1),
        ),
        if (data.newBooks.isEmpty)
          const _EmptyNote(
            icon: Icons.menu_book_rounded,
            text: 'Belum ada buku.',
          )
        else
          _BookStrip(books: data.newBooks),

        // Aktivitas terakhir
        _SectionHeader(
          title: 'Aktivitas terakhir',
          action: 'Lihat semua',
          onAction: () => openLoans(null),
        ),
        if (data.recentLoans.isEmpty)
          const _EmptyNote(
            icon: Icons.receipt_long_rounded,
            text: 'Belum ada transaksi. Ajukan peminjaman dari tab Buku.',
          )
        else
          for (final loan in data.recentLoans)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: _LoanTile(
                loan: loan,
                showStatus: true,
                onTap: () => openLoans(null),
              ),
            ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------

class _HeroCard extends StatelessWidget {
  const _HeroCard({required this.summary});

  final DashboardSummary summary;

  @override
  Widget build(BuildContext context) {
    final s = summary;
    final progress = s.loanLimit > 0
        ? (s.slotsUsed / s.loanLimit).clamp(0.0, 1.0)
        : 0.0;
    final hasOverdue = s.overdue > 0;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(26),
        gradient: const LinearGradient(
          colors: [Color(0xFF22D3EE), Color(0xFF0EA5E9), Color(0xFF0369A1)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x550EA5E9),
            blurRadius: 28,
            offset: Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Pinjaman aktif',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 4),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                '${s.activeLoans}',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 48,
                  fontWeight: FontWeight.w800,
                  height: 1.05,
                ),
              ),
              const SizedBox(width: 8),
              Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Text(
                  'buku',
                  style: TextStyle(
                    color: Colors.white.withValues(alpha: 0.85),
                    fontSize: 16,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: progress,
              minHeight: 8,
              backgroundColor: Colors.white.withValues(alpha: 0.25),
              color: Colors.white,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            '${s.slotsUsed} dari ${s.loanLimit} slot peminjaman terpakai',
            style: TextStyle(
              color: Colors.white.withValues(alpha: 0.85),
              fontSize: 12.5,
            ),
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(999),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  hasOverdue
                      ? Icons.warning_amber_rounded
                      : Icons.check_circle_outline_rounded,
                  size: 16,
                  color: Colors.white,
                ),
                const SizedBox(width: 6),
                Flexible(
                  child: Text(
                    hasOverdue
                        ? '${s.overdue} terlambat • Denda ${rupiahFormat.format(s.estimatedFine)}'
                        : 'Tidak ada keterlambatan',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({
    required this.icon,
    required this.color,
    required this.value,
    required this.label,
    required this.note,
    required this.onTap,
  });

  final IconData icon;
  final Color color;
  final String value;
  final String label;
  final String note;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return GlassCard(
      onTap: onTap,
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(
                  value,
                  style: const TextStyle(
                    fontSize: 24,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                ),
              ),
              Text(
                note,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 11, color: muted),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({required this.title, this.action, this.onAction});

  final String title;
  final String? action;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(0, 22, 0, 10),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              style: Theme.of(context).textTheme.titleMedium
                  ?.copyWith(fontWeight: FontWeight.w700),
            ),
          ),
          if (action != null)
            GestureDetector(
              onTap: onAction,
              child: Text(
                action!,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Theme.of(context).brightness == Brightness.dark
                      ? const Color(0xFF22D3EE)
                      : const Color(0xFF0284C7),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _EmptyNote extends StatelessWidget {
  const _EmptyNote({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return GlassCard(
      child: Row(
        children: [
          Icon(icon, color: muted),
          const SizedBox(width: 12),
          Expanded(
            child: Text(text, style: TextStyle(color: muted)),
          ),
        ],
      ),
    );
  }
}

class _BookStrip extends StatelessWidget {
  const _BookStrip({required this.books});

  final List<Book> books;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return SizedBox(
      height: 222,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: books.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final book = books[index];

          return SizedBox(
            width: 120,
            child: InkWell(
              borderRadius: BorderRadius.circular(14),
              onTap: () => openBookDetail(context, book),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  AspectRatio(
                    aspectRatio: 3 / 4,
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: BookCover(url: book.coverUrl),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    book.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  Text(
                    book.author,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 11, color: muted),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _LoanTile extends StatelessWidget {
  const _LoanTile({
    required this.loan,
    required this.onTap,
    this.showStatus = false,
  });

  final Loan loan;
  final VoidCallback onTap;
  final bool showStatus;

  Widget _dueInfo(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    if (loan.isOverdue) {
      return Text(
        'Terlambat ${loan.daysLate} hari • ${rupiahFormat.format(loan.fineAmount)}',
        style: TextStyle(
          color: scheme.error,
          fontSize: 12,
          fontWeight: FontWeight.w600,
        ),
      );
    }

    final left = loan.daysLeft;
    if (left == null) return const SizedBox.shrink();

    final soon = left <= 2;
    final due = loan.dueDate == null
        ? ''
        : ' • ${shortDate.format(loan.dueDate!)}';

    return Text(
      left <= 0 ? 'Jatuh tempo hari ini' : 'Sisa $left hari$due',
      style: TextStyle(
        color: soon ? const Color(0xFFD97706) : scheme.onSurfaceVariant,
        fontSize: 12,
        fontWeight: soon ? FontWeight.w600 : FontWeight.w500,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return GlassCard(
      onTap: onTap,
      padding: const EdgeInsets.all(10),
      child: Row(
        children: [
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: SizedBox(
              width: 44,
              height: 60,
              child: BookCover(url: loan.book.coverUrl),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  loan.book.title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Text(
                  loan.book.author,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(fontSize: 12, color: muted),
                ),
                const SizedBox(height: 6),
                if (showStatus)
                  Align(
                    alignment: Alignment.centerLeft,
                    child: LoanStatusBadge(status: loan.status),
                  )
                else
                  _dueInfo(context),
              ],
            ),
          ),
          Icon(Icons.chevron_right_rounded, color: muted),
        ],
      ),
    );
  }
}
