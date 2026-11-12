import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/book_provider.dart';
import '../providers/dashboard_provider.dart';
import '../providers/loan_provider.dart';
import '../widgets/glass.dart';
import 'books_screen.dart';
import 'dashboard_screen.dart';
import 'loans_screen.dart';
import 'profile_screen.dart';
import '../providers/auth_provider.dart';

class HomeShell extends StatelessWidget {
  const HomeShell({super.key});

  @override
  Widget build(BuildContext context) {
    // Provider dibuat di sini supaya otomatis baru setiap kali login
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => DashboardProvider()..refresh()),
        ChangeNotifierProvider(create: (_) => BookProvider()..init()),
        ChangeNotifierProvider(create: (_) => LoanProvider()..refresh()),
      ],
      child: const _HomeScaffold(),
    );
  }
}

class _HomeScaffold extends StatefulWidget {
  const _HomeScaffold();

  @override
  State<_HomeScaffold> createState() => _HomeScaffoldState();
}

class _HomeScaffoldState extends State<_HomeScaffold> {
  int _index = 0;

  static const List<GlassNavItem> _items = [
    GlassNavItem(
      icon: Icons.home_outlined,
      activeIcon: Icons.home_rounded,
      label: 'Beranda',
    ),
    GlassNavItem(
      icon: Icons.menu_book_outlined,
      activeIcon: Icons.menu_book_rounded,
      label: 'Buku',
    ),
    GlassNavItem(
      icon: Icons.receipt_long_outlined,
      activeIcon: Icons.receipt_long_rounded,
      label: 'Pinjaman',
    ),
    GlassNavItem(
      icon: Icons.person_outline_rounded,
      activeIcon: Icons.person_rounded,
      label: 'Profil',
    ),
  ];

  void _select(int index) {
    setState(() => _index = index);

    // Data bisa berubah oleh admin, jadi muat ulang saat tab dibuka
    if (index == 0) context.read<DashboardProvider>().refresh();
    if (index == 2) context.read<LoanProvider>().refresh();
    if (index == 0 || index == 3) context.read<AuthProvider>().refreshUser();
  }

  @override
  Widget build(BuildContext context) {
    final pages = <Widget>[
      DashboardScreen(onNavigate: _select),
      const BooksScreen(),
      const LoansScreen(),
      const ProfileScreen(),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: SafeArea(
        top: false,
        child: GlassNavBar(index: _index, items: _items, onChanged: _select),
      ),
    );
  }
}
