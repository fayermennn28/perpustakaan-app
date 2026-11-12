import 'package:flutter/material.dart';

/// Kartu kaca: putih transparan, tepi tipis terang, bayangan lembut.
class GlassCard extends StatelessWidget {
  const GlassCard({
    super.key,
    required this.child,
    this.padding = const EdgeInsets.all(16),
    this.radius = 22,
    this.onTap,
  });

  final Widget child;
  final EdgeInsets padding;
  final double radius;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final shape = BorderRadius.circular(radius);

    final fill = isDark
        ? Colors.white.withValues(alpha: 0.07)
        : Colors.white.withValues(alpha: 0.66);
    final border = isDark
        ? Colors.white.withValues(alpha: 0.12)
        : Colors.white.withValues(alpha: 0.85);
    final shadow = isDark
        ? Colors.black.withValues(alpha: 0.30)
        : const Color(0xFF032541).withValues(alpha: 0.10);

    return DecoratedBox(
      decoration: BoxDecoration(
        borderRadius: shape,
        boxShadow: [
          BoxShadow(color: shadow, blurRadius: 24, offset: const Offset(0, 8)),
        ],
      ),
      child: Material(
        color: fill,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: shape,
          side: BorderSide(color: border),
        ),
        child: InkWell(
          onTap: onTap,
          child: Padding(padding: padding, child: child),
        ),
      ),
    );
  }
}

class GlassNavItem {
  final IconData icon;
  final IconData activeIcon;
  final String label;

  const GlassNavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
  });
}

/// Navigasi bawah melayang berbentuk pil, menu aktif bergradasi cyan.
class GlassNavBar extends StatelessWidget {
  const GlassNavBar({
    super.key,
    required this.index,
    required this.items,
    required this.onChanged,
  });

  final int index;
  final List<GlassNavItem> items;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 12),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: isDark
              ? const Color(0xFF0F1E33).withValues(alpha: 0.92)
              : Colors.white.withValues(alpha: 0.82),
          borderRadius: BorderRadius.circular(28),
          border: Border.all(
            color: isDark ? Colors.white.withValues(alpha: 0.12) : Colors.white,
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: isDark ? 0.40 : 0.12),
              blurRadius: 24,
              offset: const Offset(0, 8),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(6),
          child: Row(
            children: [
              for (var i = 0; i < items.length; i++)
                Expanded(
                  child: _NavButton(
                    item: items[i],
                    selected: i == index,
                    onTap: () => onChanged(i),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

class _NavButton extends StatelessWidget {
  const _NavButton({
    required this.item,
    required this.selected,
    required this.onTap,
  });

  final GlassNavItem item;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;
    final color = selected ? Colors.white : muted;

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 220),
        curve: Curves.easeOut,
        padding: const EdgeInsets.symmetric(vertical: 8),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          gradient: selected
              ? const LinearGradient(
                  colors: [Color(0xFF22D3EE), Color(0xFF0EA5E9)],
                )
              : null,
          boxShadow: selected
              ? const [
                  BoxShadow(
                    color: Color(0x660EA5E9),
                    blurRadius: 14,
                    offset: Offset(0, 5),
                  ),
                ]
              : null,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              selected ? item.activeIcon : item.icon,
              size: 22,
              color: color,
            ),
            const SizedBox(height: 2),
            Text(
              item.label,
              style: TextStyle(
                fontSize: 11,
                fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Chip filter berbentuk pil (aktif: warna utama, tidak aktif: kaca).
class GlassChip extends StatelessWidget {
  const GlassChip({
    super.key,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;

    final fill = isDark
        ? Colors.white.withValues(alpha: 0.07)
        : Colors.white.withValues(alpha: 0.7);
    final border = isDark
        ? Colors.white.withValues(alpha: 0.14)
        : Colors.white.withValues(alpha: 0.9);

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 9),
        decoration: BoxDecoration(
          color: selected ? scheme.primary : fill,
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: selected ? Colors.transparent : border),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: selected ? scheme.onPrimary : scheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }
}

/// Dekorasi input bergaya kaca (dipakai di pencarian dan form login).
InputDecoration glassInputDecoration(
  BuildContext context, {
  String? label,
  String? hint,
  IconData? prefix,
  Widget? suffix,
  double radius = 16,
}) {
  final isDark = Theme.of(context).brightness == Brightness.dark;
  final scheme = Theme.of(context).colorScheme;

  final fill = isDark
      ? Colors.white.withValues(alpha: 0.07)
      : Colors.white.withValues(alpha: 0.7);
  final borderColor = isDark
      ? Colors.white.withValues(alpha: 0.14)
      : Colors.white.withValues(alpha: 0.9);
  final focusColor = isDark ? const Color(0xFF22D3EE) : const Color(0xFF0EA5E9);

  OutlineInputBorder border(Color color, [double width = 1]) {
    return OutlineInputBorder(
      borderRadius: BorderRadius.circular(radius),
      borderSide: BorderSide(color: color, width: width),
    );
  }

  return InputDecoration(
    labelText: label,
    hintText: hint,
    prefixIcon: prefix != null ? Icon(prefix) : null,
    suffixIcon: suffix,
    filled: true,
    fillColor: fill,
    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    border: border(borderColor),
    enabledBorder: border(borderColor),
    focusedBorder: border(focusColor, 1.6),
    errorBorder: border(scheme.error),
    focusedErrorBorder: border(scheme.error, 1.6),
  );
}
