import 'package:flutter/material.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';
import 'package:flutter/services.dart';

import 'widgets/app_background.dart';
import 'core/theme.dart';
import 'providers/auth_provider.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'screens/splash_screen.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()..bootstrap()),
      ],
      child: const PerpustakaanApp(),
    ),
  );
}

class PerpustakaanApp extends StatelessWidget {
  const PerpustakaanApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Perpustakaan',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      themeMode: ThemeMode.system,
      builder: (context, child) =>
          AppBackground(child: child ?? const SizedBox.shrink()),
      home: const AuthGate(),
    );
  }
}

/// Memilih layar berdasarkan status login.
class AuthGate extends StatelessWidget {
  const AuthGate({super.key});

  @override
  Widget build(BuildContext context) {
    final status = context.watch<AuthProvider>().status;

    return switch (status) {
      AuthStatus.unknown => const SplashScreen(),
      AuthStatus.authenticated => const HomeShell(),
      AuthStatus.unauthenticated => const LoginScreen(),
    };
  }
}
