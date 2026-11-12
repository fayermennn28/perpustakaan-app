import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

/// Latar gradasi + "blob" cahaya lembut, seperti di website admin.
class AppBackground extends StatelessWidget {
  const AppBackground({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final colors = isDark
        ? const [Color(0xFF040A16), Color(0xFF082033), Color(0xFF0A3A4A)]
        : const [Color(0xFFDDF1FA), Color(0xFFBFE6F3), Color(0xFFA7DCEB)];

    final overlay =
        (isDark ? SystemUiOverlayStyle.light : SystemUiOverlayStyle.dark)
            .copyWith(statusBarColor: Colors.transparent);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: overlay,
      child: Stack(
        fit: StackFit.expand,
        children: [
          DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: colors,
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
            ),
          ),
          const _Blob(
            alignment: Alignment(-1.0, -0.9),
            color: Color(0x5522D3EE),
            size: 320,
          ),
          const _Blob(
            alignment: Alignment(1.1, -0.2),
            color: Color(0x440EA5E9),
            size: 300,
          ),
          const _Blob(
            alignment: Alignment(-0.3, 1.1),
            color: Color(0x3310B981),
            size: 340,
          ),
          child,
        ],
      ),
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({
    required this.alignment,
    required this.color,
    required this.size,
  });

  final Alignment alignment;
  final Color color;
  final double size;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: alignment,
      child: IgnorePointer(
        child: Container(
          width: size,
          height: size,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: RadialGradient(
              colors: [color, color.withValues(alpha: 0)],
            ),
          ),
        ),
      ),
    );
  }
}
