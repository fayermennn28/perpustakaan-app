import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/book.dart';
import '../providers/loan_provider.dart';
import '../screens/book_detail_screen.dart';

/// Halaman detail berada di rute baru, jadi LoanProvider diteruskan manual.
void openBookDetail(BuildContext context, Book book) {
  final loanProvider = context.read<LoanProvider>();

  Navigator.of(context).push(
    MaterialPageRoute(
      builder: (_) => ChangeNotifierProvider.value(
        value: loanProvider,
        child: BookDetailScreen(book: book),
      ),
    ),
  );
}
