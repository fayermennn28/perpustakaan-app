import 'book.dart';
import 'loan.dart';

int _i(dynamic value) {
  if (value is int) return value;
  return int.tryParse(value?.toString() ?? '') ?? 0;
}

class DashboardSummary {
  final int activeLoans;
  final int pendingRequests;
  final int pendingReturns;
  final int overdue;
  final int estimatedFine;
  final int loanLimit;
  final int slotsUsed;

  const DashboardSummary({
    required this.activeLoans,
    required this.pendingRequests,
    required this.pendingReturns,
    required this.overdue,
    required this.estimatedFine,
    required this.loanLimit,
    required this.slotsUsed,
  });

  factory DashboardSummary.fromJson(Map<String, dynamic> json) {
    return DashboardSummary(
      activeLoans: _i(json['active_loans']),
      pendingRequests: _i(json['pending_requests']),
      pendingReturns: _i(json['pending_returns']),
      overdue: _i(json['overdue']),
      estimatedFine: _i(json['estimated_fine']),
      loanLimit: _i(json['loan_limit']),
      slotsUsed: _i(json['slots_used']),
    );
  }
}

class DashboardData {
  final DashboardSummary summary;
  final List<Loan> dueSoon;
  final List<Loan> recentLoans;
  final List<Book> newBooks;
  final List<Book> popularBooks;

  const DashboardData({
    required this.summary,
    required this.dueSoon,
    required this.recentLoans,
    required this.newBooks,
    required this.popularBooks,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    List<T> list<T>(dynamic value, T Function(Map<String, dynamic>) parse) {
      return (value as List? ?? [])
          .map((e) => parse(e as Map<String, dynamic>))
          .toList();
    }

    return DashboardData(
      summary: DashboardSummary.fromJson(
        json['summary'] as Map<String, dynamic>,
      ),
      dueSoon: list(json['due_soon'], Loan.fromJson),
      recentLoans: list(json['recent_loans'], Loan.fromJson),
      newBooks: list(json['new_books'], Book.fromJson),
      popularBooks: list(json['popular_books'], Book.fromJson),
    );
  }
}
