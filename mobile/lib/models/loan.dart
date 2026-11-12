import 'book.dart';

int _toInt(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

DateTime? _toDate(dynamic value, {bool local = false}) {
  if (value == null) return null;
  final parsed = DateTime.tryParse(value.toString());
  if (parsed == null) return null;
  return local ? parsed.toLocal() : parsed;
}

class Loan {
  final int id;
  final String status;
  final Book book;
  final DateTime? requestedAt;
  final DateTime? approvedAt;
  final DateTime? dueDate;
  final DateTime? returnRequestedAt;
  final DateTime? returnedAt;
  final bool isOverdue;
  final int daysLate;
  final int fineAmount;
  final String? adminNote;

  const Loan({
    required this.id,
    required this.status,
    required this.book,
    this.requestedAt,
    this.approvedAt,
    this.dueDate,
    this.returnRequestedAt,
    this.returnedAt,
    required this.isOverdue,
    required this.daysLate,
    required this.fineAmount,
    this.adminNote,
  });

  factory Loan.fromJson(Map<String, dynamic> json) {
    return Loan(
      id: _toInt(json['id']),
      status: json['status'] as String,
      book: Book.fromJson(json['book'] as Map<String, dynamic>),
      requestedAt: _toDate(json['requested_at'], local: true),
      approvedAt: _toDate(json['approved_at'], local: true),
      dueDate: _toDate(json['due_date']),
      returnRequestedAt: _toDate(json['return_requested_at'], local: true),
      returnedAt: _toDate(json['returned_at'], local: true),
      isOverdue: json['is_overdue'] as bool? ?? false,
      daysLate: _toInt(json['days_late']),
      fineAmount: _toInt(json['fine_amount']),
      adminNote: json['admin_note'] as String?,
    );
  }

  /// Sisa hari sampai jatuh tempo (0 = hari ini, negatif = lewat).
  int? get daysLeft {
    if (dueDate == null) return null;
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    return (dueDate!.difference(today).inHours / 24).round();
  }
}
