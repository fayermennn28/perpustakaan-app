import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'glass.dart';
import '../models/loan.dart';
import '../providers/loan_provider.dart';
import 'book_cover.dart';
import 'loan_status.dart';

class LoanCard extends StatefulWidget {
  const LoanCard({super.key, required this.loan});

  final Loan loan;

  @override
  State<LoanCard> createState() => _LoanCardState();
}

class _LoanCardState extends State<LoanCard> {
  static final _date = DateFormat('d MMM yyyy', 'id_ID');
  static final _rupiah = NumberFormat.currency(
    locale: 'id_ID',
    symbol: 'Rp ',
    decimalDigits: 0,
  );

  bool _busy = false;

  String _fmt(DateTime? value) => value == null ? '-' : _date.format(value);

  Future<void> _requestReturn() async {
    final loan = widget.loan;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Ajukan pengembalian?'),
        content: Text(
          'Ajukan pengembalian "${loan.book.title}". '
          'Serahkan bukunya ke petugas perpustakaan untuk diverifikasi.'
          '${loan.isOverdue ? '\n\nBuku ini terlambat ${loan.daysLate} hari. '
                    'Denda berjalan ${_rupiah.format(loan.fineAmount)} '
                    '(denda final dihitung saat admin menerima buku).' : ''}',
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

    final messenger = ScaffoldMessenger.of(context);
    final provider = context.read<LoanProvider>();
    final errorColor = Theme.of(context).colorScheme.error;

    setState(() => _busy = true);
    final error = await provider.requestReturn(loan.id);
    if (mounted) setState(() => _busy = false);

    messenger.showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        backgroundColor: error == null ? null : errorColor,
        content: Text(
          error ?? 'Pengajuan pengembalian terkirim. Serahkan buku ke admin.',
        ),
      ),
    );
  }

  Widget _line(IconData icon, String text, {Color? color, FontWeight? weight}) {
    final c = color ?? Theme.of(context).colorScheme.onSurfaceVariant;

    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: c),
          const SizedBox(width: 6),
          Expanded(
            child: Text(
              text,
              style: TextStyle(fontSize: 13, color: c, fontWeight: weight),
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _details(BuildContext context) {
    final loan = widget.loan;
    final scheme = Theme.of(context).colorScheme;
    const green = Color(0xFF059669);
    const amber = Color(0xFFD97706);

    switch (loan.status) {
      case 'diajukan':
        return [
          _line(Icons.event_outlined, 'Diajukan ${_fmt(loan.requestedAt)}'),
          _line(Icons.schedule_rounded, 'Menunggu persetujuan admin'),
        ];

      case 'ditolak':
        return [
          _line(Icons.event_outlined, 'Diajukan ${_fmt(loan.requestedAt)}'),
        ];

      case 'dipinjam':
      case 'pengembalian_diajukan':
        final left = loan.daysLeft;
        return [
          _line(Icons.event_outlined, 'Jatuh tempo ${_fmt(loan.dueDate)}'),
          if (loan.isOverdue)
            _line(
              Icons.warning_amber_rounded,
              'Terlambat ${loan.daysLate} hari • Denda berjalan ${_rupiah.format(loan.fineAmount)}',
              color: scheme.error,
              weight: FontWeight.w600,
            )
          else if (left != null)
            _line(
              Icons.timelapse_rounded,
              left <= 0 ? 'Jatuh tempo hari ini' : 'Sisa $left hari',
              color: left <= 2 ? amber : null,
              weight: left <= 2 ? FontWeight.w600 : null,
            ),
        ];

      case 'dikembalikan':
        return [
          _line(
            Icons.event_available_outlined,
            'Dikembalikan ${_fmt(loan.returnedAt)}',
          ),
          if (loan.fineAmount > 0)
            _line(
              Icons.payments_outlined,
              'Denda ${_rupiah.format(loan.fineAmount)}',
              color: scheme.error,
              weight: FontWeight.w600,
            )
          else
            _line(
              Icons.verified_outlined,
              'Tepat waktu',
              color: green,
              weight: FontWeight.w600,
            ),
        ];

      default:
        return const [];
    }
  }

  @override
  Widget build(BuildContext context) {
    final loan = widget.loan;
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    return GlassCard(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: SizedBox(
                  width: 64,
                  height: 88,
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
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      loan.book.author,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: textTheme.bodySmall?.copyWith(
                        color: scheme.onSurfaceVariant,
                      ),
                    ),
                    const SizedBox(height: 8),
                    LoanStatusBadge(status: loan.status),
                    ..._details(context),
                  ],
                ),
              ),
            ],
          ),

          if (loan.status == 'ditolak' &&
              loan.adminNote != null &&
              loan.adminNote!.trim().isNotEmpty) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: scheme.errorContainer.withValues(alpha: 0.8),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                'Alasan: ${loan.adminNote}',
                style: TextStyle(color: scheme.onErrorContainer, fontSize: 13),
              ),
            ),
          ],

          if (loan.status == 'dipinjam') ...[
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: FilledButton.tonalIcon(
                onPressed: _busy ? null : _requestReturn,
                style: FilledButton.styleFrom(shape: const StadiumBorder()),
                icon: _busy
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.assignment_return_outlined),
                label: const Text('Ajukan Pengembalian'),
              ),
            ),
          ],

          if (loan.status == 'pengembalian_diajukan') ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: scheme.primaryContainer.withValues(alpha: 0.6),
                borderRadius: BorderRadius.circular(14),
              ),
              child: Text(
                'Menunggu verifikasi admin. Serahkan bukunya ke petugas perpustakaan.',
                style: TextStyle(
                  color: scheme.onPrimaryContainer,
                  fontSize: 13,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
