// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';
import 'package:perpustakaan_mobile/models/loan.dart';

void main() {
  test('Loan.fromJson membaca status, buku, keterlambatan, dan denda', () {
    final loan = Loan.fromJson({
      'id': 1,
      'status': 'dipinjam',
      'book': {
        'id': 5,
        'title': 'Belajar Flutter',
        'author': 'Penulis Uji',
        'stock': 3,
        'available': true,
      },
      'due_date': '2026-11-20',
      'is_overdue': true,
      'days_late': 2,
      'fine_amount': 2000,
    });

    expect(loan.status, 'dipinjam');
    expect(loan.book.title, 'Belajar Flutter');
    expect(loan.isOverdue, isTrue);
    expect(loan.daysLate, 2);
    expect(loan.fineAmount, 2000);
    expect(loan.dueDate, DateTime(2026, 11, 20));
  });
}
