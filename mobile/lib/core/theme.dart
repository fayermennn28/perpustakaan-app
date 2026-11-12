import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  static const Color seed = Color(0xFF0EA5E9);
  static const Color inkLight = Color(0xFF0B2540);
  static const Color accentDark = Color(0xFF22D3EE);

  static ThemeData get light => _build(Brightness.light);
  static ThemeData get dark => _build(Brightness.dark);

  static ThemeData _build(Brightness brightness) {
    final isDark = brightness == Brightness.dark;

    final scheme = ColorScheme.fromSeed(seedColor: seed, brightness: brightness)
        .copyWith(
          primary: isDark ? accentDark : inkLight,
          onPrimary: isDark ? const Color(0xFF04222E) : Colors.white,
        );

    return ThemeData(
      useMaterial3: true,
      brightness: brightness,
      colorScheme: scheme,
      scaffoldBackgroundColor: Colors.transparent,

      // Font Inter
      fontFamily: GoogleFonts.inter().fontFamily,

      appBarTheme: AppBarTheme(
        backgroundColor: Colors.transparent,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        scrolledUnderElevation: 0,
        titleTextStyle: GoogleFonts.inter(
          fontSize: 20,
          fontWeight: FontWeight.w700,
          color: scheme.onSurface,
        ),
      ),
    );
  }
}
