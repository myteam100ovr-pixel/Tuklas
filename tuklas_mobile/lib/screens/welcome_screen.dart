import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../theme.dart';
import '../ui.dart';

/// Remembers completion on this installation, independently of sign-in.
class FirstLaunch extends StatefulWidget {
  const FirstLaunch({super.key, required this.child});

  final Widget child;
  static const preferenceKey = 'tuklas-welcome-completed-v1';

  @override
  State<FirstLaunch> createState() => _FirstLaunchState();
}

class _FirstLaunchState extends State<FirstLaunch> {
  bool? _completed;
  bool _saving = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    var completed = false;
    try {
      final preferences = await SharedPreferences.getInstance();
      completed = preferences.getBool(FirstLaunch.preferenceKey) ?? false;
    } catch (_) {
      // The welcome screen remains available if local storage is unavailable.
    }
    if (mounted) setState(() => _completed = completed);
  }

  Future<void> _continue() async {
    setState(() {
      _saving = true;
      _error = null;
    });
    try {
      final preferences = await SharedPreferences.getInstance();
      final saved = await preferences.setBool(FirstLaunch.preferenceKey, true);
      if (!saved) throw StateError('Unable to save welcome preference');
      if (mounted) setState(() => _completed = true);
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'Could not save your preference. Please try again.',
        );
      }
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_completed == true) return widget.child;
    final c = TkColors.of(context);
    return Scaffold(
      body: SafeArea(
        child: _completed == null
            ? const Center(child: CircularProgressIndicator())
            : Center(
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 560),
                  child: ListView(
                    padding: const EdgeInsets.all(24),
                    children: [
                      Text(
                        'tuklas.',
                        style: tkText(
                          context,
                          size: 34,
                          weight: 700,
                          spacing: -1.8,
                        ),
                      ),
                      const SizedBox(height: 32),
                      Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: TkColors.violet.withValues(alpha: .1),
                          borderRadius: BorderRadius.circular(24),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Icon(
                              Icons.auto_awesome,
                              color: c.taskMeta,
                              size: 36,
                            ),
                            const SizedBox(height: 20),
                            const TkPageHeading(
                              eyebrow: 'WELCOME TO YOUR NEXT STEP',
                              title: 'What to expect',
                            ),
                            Text(
                              'Discover your skills. Explore possibilities. Build a path that feels like you.',
                              style: tkText(
                                context,
                                size: 15,
                                height: 1.5,
                                color: c.ink2,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),
                      _feature(
                        context,
                        Icons.document_scanner_outlined,
                        '01',
                        'Discover what you bring',
                        'Upload a resume or certificate. The AI scanner helps identify your skills and possible next steps.',
                      ),
                      _feature(
                        context,
                        Icons.school_outlined,
                        '02',
                        'Explore careers and TESDA',
                        'See scan-based career and training recommendations. These are guidance, not confirmed jobs or guaranteed training slots.',
                      ),
                      _feature(
                        context,
                        Icons.person_outline,
                        '03',
                        'Make it yours',
                        'Sign in or create an account, complete your profile, and keep your scans and saved details connected across mobile and web.',
                      ),
                      Text(
                        'AI features require a connection. Documents you choose to scan are sent for AI analysis; review suggestions before acting on them.',
                        style: tkText(
                          context,
                          size: 12,
                          height: 1.5,
                          color: c.muted,
                        ),
                      ),
                      if (_error != null)
                        Padding(
                          padding: const EdgeInsets.only(top: 12),
                          child: Text(
                            _error!,
                            style: tkText(context, color: c.bad),
                          ),
                        ),
                      const SizedBox(height: 24),
                      FilledButton(
                        onPressed: _saving ? null : _continue,
                        style: FilledButton.styleFrom(
                          minimumSize: const Size.fromHeight(52),
                        ),
                        child: Text(_saving ? 'Getting ready…' : 'Get started'),
                      ),
                      const SizedBox(height: 12),
                    ],
                  ),
                ),
              ),
      ),
    );
  }

  Widget _feature(
    BuildContext context,
    IconData icon,
    String number,
    String title,
    String description,
  ) {
    final c = TkColors.of(context);
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: c.surface2,
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: c.taskMeta, size: 22),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '$number / $title',
                  style: tkText(context, size: 15, weight: 600),
                ),
                const SizedBox(height: 6),
                Text(
                  description,
                  style: tkText(context, size: 13, height: 1.5, color: c.ink2),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
