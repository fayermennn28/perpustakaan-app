import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../models/user.dart';

class UserAvatar extends StatelessWidget {
  const UserAvatar({super.key, required this.user, this.radius = 24});

  final User user;
  final double radius;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final size = radius * 2;
    final initial = user.name.isNotEmpty ? user.name[0].toUpperCase() : '?';

    final fallback = Container(
      width: size,
      height: size,
      alignment: Alignment.center,
      color: scheme.primaryContainer,
      child: Text(
        initial,
        style: TextStyle(
          fontSize: radius * 0.8,
          fontWeight: FontWeight.w700,
          color: scheme.onPrimaryContainer,
        ),
      ),
    );

    final url = user.photoUrl;

    return SizedBox(
      width: size,
      height: size,
      child: ClipOval(
        child: url == null
            ? fallback
            : CachedNetworkImage(
                imageUrl: url,
                width: size,
                height: size,
                fit: BoxFit.cover,
                placeholder: (_, __) => fallback,
                errorWidget: (_, failedUrl, error) {
                  debugPrint('Foto gagal dimuat: $failedUrl -> $error');
                  return fallback;
                },
              ),
      ),
    );
  }
}
