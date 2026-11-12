import 'package:flutter/material.dart';

class LoanStatusStyle {
  final String label;
  final Color color;
  final IconData icon;

  const LoanStatusStyle(this.label, this.color, this.icon);
}

LoanStatusStyle loanStatusStyle(String status) {
  switch (status) {
    case 'diajukan':
      return const LoanStatusStyle(
        'Diajukan',
        Color(0xFFD97706),
        Icons.hourglass_top_rounded,
      );
    case 'dipinjam':
      return const LoanStatusStyle(
        'Dipinjam',
        Color(0xFF0284C7),
        Icons.menu_book_rounded,
      );
    case 'pengembalian_diajukan':
      return const LoanStatusStyle(
        'Pengajuan kembali',
        Color(0xFF7C3AED),
        Icons.assignment_return_rounded,
      );
    case 'dikembalikan':
      return const LoanStatusStyle(
        'Dikembalikan',
        Color(0xFF059669),
        Icons.check_circle_rounded,
      );
    case 'ditolak':
      return const LoanStatusStyle(
        'Ditolak',
        Color(0xFFDC2626),
        Icons.cancel_rounded,
      );
    default:
      return LoanStatusStyle(status, Colors.grey, Icons.help_outline_rounded);
  }
}

class LoanStatusBadge extends StatelessWidget {
  const LoanStatusBadge({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final style = loanStatusStyle(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: style.color.withValues(alpha: 0.14),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(style.icon, size: 14, color: style.color),
          const SizedBox(width: 4),
          Text(
            style.label,
            style: TextStyle(
              color: style.color,
              fontSize: 12,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}
