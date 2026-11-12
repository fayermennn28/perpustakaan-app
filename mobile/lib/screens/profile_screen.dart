import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import '../widgets/glass.dart';
import '../widgets/user_avatar.dart';

class ProfileScreen extends StatelessWidget {
  const ProfileScreen({super.key});

  Future<void> _confirmLogout(BuildContext context) async {
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Keluar'),
        content: const Text('Yakin ingin keluar dari akun ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Batal'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );

    if (ok == true && context.mounted) {
      await context.read<AuthProvider>().logout();
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final user = auth.user;
    final scheme = Theme.of(context).colorScheme;
    final textTheme = Theme.of(context).textTheme;

    if (user == null) return const SizedBox.shrink();

    return Scaffold(
      appBar: AppBar(title: const Text('Profil')),
      body: RefreshIndicator(
        onRefresh: auth.refreshUser,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
          children: [
            GlassCard(
              radius: 28,
              padding: const EdgeInsets.all(24),
              child: Column(
                children: [
                  UserAvatar(user: user, radius: 52),
                  const SizedBox(height: 16),
                  Text(
                    user.name,
                    textAlign: TextAlign.center,
                    style: textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  if (user.memberCode != null) ...[
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 14,
                        vertical: 7,
                      ),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0EA5E9).withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.badge_outlined,
                            size: 16,
                            color: Color(0xFF0284C7),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            user.memberCode!,
                            style: const TextStyle(
                              color: Color(0xFF0284C7),
                              fontWeight: FontWeight.w700,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),
            GlassCard(
              padding: const EdgeInsets.symmetric(vertical: 4),
              child: Column(
                children: [
                  _InfoTile(
                    icon: Icons.mail_outline_rounded,
                    label: 'Email',
                    value: user.email,
                  ),
                  const Divider(height: 1),
                  _InfoTile(
                    icon: Icons.phone_outlined,
                    label: 'Telepon',
                    value: user.phone,
                  ),
                  const Divider(height: 1),
                  _InfoTile(
                    icon: Icons.location_on_outlined,
                    label: 'Alamat',
                    value: user.address,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            OutlinedButton.icon(
              onPressed: () => _confirmLogout(context),
              icon: const Icon(Icons.logout_rounded),
              label: const Text('Keluar'),
              style: OutlinedButton.styleFrom(
                foregroundColor: scheme.error,
                side: BorderSide(color: scheme.error.withValues(alpha: 0.5)),
                minimumSize: const Size.fromHeight(50),
                shape: const StadiumBorder(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile({
    required this.icon,
    required this.label,
    required this.value,
  });

  final IconData icon;
  final String label;
  final String? value;

  @override
  Widget build(BuildContext context) {
    final muted = Theme.of(context).colorScheme.onSurfaceVariant;

    return ListTile(
      leading: Icon(icon, color: muted),
      title: Text(label, style: TextStyle(fontSize: 12, color: muted)),
      subtitle: Text(
        (value == null || value!.isEmpty) ? '-' : value!,
        style: Theme.of(context).textTheme.bodyLarge
            ?.copyWith(fontWeight: FontWeight.w600),
      ),
    );
  }
}
