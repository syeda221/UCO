import 'package:flutter/material.dart';

class UcoColors {
  static const canvas = Color(0xFFF5F5F3);
  static const card = Color(0xFFFFFFFF);
  static const charcoal = Color(0xFF1A1C19);
  static const charcoalSurface = Color(0xFF272A25);
  static const lime = Color(0xFFB6F500);
  static const ink = Color(0xFF141513);
  static const mutedInk = Color(0xFF7A7D78);
  static const border = Color(0xFFE6E7E3);
  static const amber = Color(0xFFF59E0B);
  static const danger = Color(0xFFBA1A1A);
}

class UcoTheme {
  static ThemeData light() {
    return ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: UcoColors.canvas,
      colorScheme: const ColorScheme.light(
        primary: UcoColors.charcoal,
        secondary: UcoColors.lime,
        surface: UcoColors.card,
        error: UcoColors.danger,
        onPrimary: Colors.white,
        onSecondary: UcoColors.ink,
        onSurface: UcoColors.ink,
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: UcoColors.canvas,
        foregroundColor: UcoColors.ink,
        elevation: 0,
        scrolledUnderElevation: 0,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: UcoColors.canvas,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(18),
          borderSide: const BorderSide(color: UcoColors.charcoal, width: 1.5),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      ),
    );
  }
}
