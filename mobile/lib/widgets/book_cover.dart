import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

class BookCover extends StatelessWidget {
  const BookCover({super.key, required this.url});

  final String? url;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    final placeholder = Container(
      color: scheme.primaryContainer.withValues(alpha: 0.5),
      child: Center(
        child: Icon(Icons.menu_book_rounded, size: 40, color: scheme.primary),
      ),
    );

    if (url == null) return placeholder;

    return CachedNetworkImage(
      imageUrl: url!,
      fit: BoxFit.cover,
      placeholder: (_, __) => placeholder,
      errorWidget: (_, __, ___) => placeholder,
    );
  }
}
