import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import 'tuklas_api.dart';
import 'tuklas_theme.dart';

class TuklasLandingScreen extends StatelessWidget {
  const TuklasLandingScreen({
    super.key,
    required this.onLogin,
    required this.onRegister,
    required this.onCatalog,
    required this.onSettings,
    required this.isDark,
    required this.onToggleTheme,
  });

  final VoidCallback onLogin;
  final VoidCallback onRegister;
  final VoidCallback onCatalog;
  final VoidCallback onSettings;
  final bool isDark;
  final VoidCallback onToggleTheme;

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    return Scaffold(
      appBar: AppBar(
        titleSpacing: 20,
        title: const _TuklasWordmark(),
        actions: [
          IconButton(
            tooltip: isDark ? 'Use light theme' : 'Use dark theme',
            onPressed: onToggleTheme,
            icon: Icon(isDark ? Icons.light_mode_outlined : Icons.dark_mode_outlined),
          ),
          IconButton(
            tooltip: 'Server settings',
            onPressed: onSettings,
            icon: const Icon(Icons.tune),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 10, 20, 36),
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
            decoration: BoxDecoration(
              color: Theme.of(context).cardTheme.color,
              border: Border.all(color: colors.outline),
              borderRadius: BorderRadius.circular(99),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.auto_awesome, color: TuklasColors.violet, size: 15),
                SizedBox(width: 7),
                Text('Powered by Google Gemini AI', style: TextStyle(fontSize: 11)),
              ],
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'AI-Powered Career Path and Skills Development for Youth in Pangasinan',
            style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.w500,
                  height: 1.12,
                ),
          ),
          const SizedBox(height: 9),
          Text(
            'Piloting in Bugallon, with TESDA Lingayen trainings.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: colors.onSurfaceVariant,
                  height: 1.5,
                ),
          ),
          const SizedBox(height: 20),
          const _LandingArtwork(),
          const SizedBox(height: 26),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              FilledButton(
                onPressed: onRegister,
                child: const Text('Get started'),
              ),
              OutlinedButton(
                onPressed: onLogin,
                child: const Text('Log in'),
              ),
              TextButton.icon(
                onPressed: onCatalog,
                icon: const Icon(Icons.open_in_new, size: 17),
                label: const Text('TESDA resources'),
              ),
            ],
          ),
          const SizedBox(height: 32),
          const _SectionHeading(
            eyebrow: 'A path that starts with you',
            title: 'Guidance built around you',
          ),
          const SizedBox(height: 8),
          Text(
            'Share your interests and skills to help shape career and training guidance. Suggestions are guidance, not guarantees of jobs, admission, or training slots.',
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  color: colors.onSurfaceVariant,
                  height: 1.55,
                ),
          ),
          const SizedBox(height: 16),
          const _InterestChips(),
          const SizedBox(height: 28),
          const _SectionHeading(title: 'Your Tuklas pathway'),
          const SizedBox(height: 6),
          const _PathwayRow(number: '01', title: 'Tell us about you', color: TuklasColors.orange),
          const _PathwayRow(number: '02', title: 'Take the skills assessment', color: TuklasColors.yellow, planned: true),
          const _PathwayRow(number: '03', title: 'Explore careers and trainings', color: TuklasColors.violet, planned: true),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Theme.of(context).cardTheme.color,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: colors.outline),
            ),
            child: Text(
              'Resume and certificate scanning is optional. If you scan a document, a copy is sent to Google Gemini for analysis.',
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                    color: colors.onSurfaceVariant,
                    height: 1.5,
                  ),
            ),
          ),
          const SizedBox(height: 30),
          Center(
            child: TextButton.icon(
              onPressed: onCatalog,
              icon: const Icon(Icons.school_outlined),
              label: const Text('Explore TESDA training and services'),
            ),
          ),
        ],
      ),
    );
  }
}

class TuklasAuthScreen extends StatefulWidget {
  const TuklasAuthScreen({
    super.key,
    required this.api,
    required this.initialMode,
    required this.onAuthenticated,
    required this.onSocialSignIn,
    required this.onBack,
  });

  final TuklasApi api;
  final bool initialMode;
  final ValueChanged<Map<String, dynamic>> onAuthenticated;
  final ValueChanged<String> onSocialSignIn;
  final VoidCallback onBack;

  @override
  State<TuklasAuthScreen> createState() => _TuklasAuthScreenState();
}

class _TuklasAuthScreenState extends State<TuklasAuthScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _codeController = TextEditingController();
  final _recoveryController = TextEditingController();
  bool _isRegistering = false;
  bool _isForgotPassword = false;
  bool _awaitingTwoFactor = false;
  bool _useRecoveryCode = false;
  bool _acceptedTerms = false;
  bool _isBusy = false;
  String? _status;
  String? _error;

  @override
  void initState() {
    super.initState();
    _isRegistering = widget.initialMode;
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _codeController.dispose();
    _recoveryController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (_isRegistering && !_acceptedTerms) {
      setState(() => _error = 'Accept the terms and privacy policy to create an account.');
      return;
    }

    setState(() {
      _isBusy = true;
      _error = null;
      _status = null;
    });
    try {
      final result = _isForgotPassword
          ? await _requestReset()
          : _isRegistering
              ? await widget.api.register(
                  name: _nameController.text.trim(),
                  email: _emailController.text.trim(),
                  password: _passwordController.text,
                  deviceName: 'Tuklas mobile',
                  acceptedTerms: _acceptedTerms,
                )
              : await widget.api.login(
                  email: _emailController.text.trim(),
                  password: _passwordController.text,
                  deviceName: 'Tuklas mobile',
                  code: _awaitingTwoFactor && !_useRecoveryCode ? _codeController.text.trim() : null,
                  recoveryCode: _awaitingTwoFactor && _useRecoveryCode ? _recoveryController.text.trim() : null,
                );

      if (_isForgotPassword) {
        setState(() => _status = 'If an account exists for this email, a password reset link has been sent.');
      } else {
        widget.onAuthenticated(Map<String, dynamic>.from(result['user'] as Map));
      }
    } catch (error) {
      if (TuklasApi.needsTwoFactor(error)) {
        setState(() => _awaitingTwoFactor = true);
      } else {
        setState(() => _error = TuklasApi.errorMessage(error));
      }
    } finally {
      if (mounted) setState(() => _isBusy = false);
    }
  }

  Future<Map<String, dynamic>> _requestReset() async {
    await widget.api.requestPasswordReset(_emailController.text.trim());
    return <String, dynamic>{};
  }

  @override
  Widget build(BuildContext context) {
    final colors = Theme.of(context).colorScheme;
    final title = _isForgotPassword
        ? 'Reset your password'
        : _awaitingTwoFactor
            ? 'Two-factor check'
            : _isRegistering
                ? 'Create your account'
                : 'Welcome back';

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          tooltip: 'Back',
          onPressed: widget.onBack,
          icon: const Icon(Icons.arrow_back),
        ),
        title: const _TuklasWordmark(),
      ),
      body: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 520),
          child: ListView(
            padding: const EdgeInsets.fromLTRB(22, 18, 22, 32),
            children: [
              Text(
                _awaitingTwoFactor ? 'SECURITY CHECK' : 'TUKLAS ACCOUNT',
                style: Theme.of(context).textTheme.labelSmall?.copyWith(
                      color: TuklasColors.violet,
                      fontWeight: FontWeight.w700,
                    ),
              ),
              const SizedBox(height: 8),
              Text(title, style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 18),
              if (_error != null) _MessageBanner(text: _error!, isError: true),
              if (_status != null) _MessageBanner(text: _status!, isError: false),
              Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (_isRegistering && !_awaitingTwoFactor && !_isForgotPassword) ...[
                      TextFormField(
                        controller: _nameController,
                        textCapitalization: TextCapitalization.words,
                        decoration: const InputDecoration(labelText: 'Full name'),
                        validator: _required,
                      ),
                      const SizedBox(height: 12),
                    ],
                    if (!_awaitingTwoFactor) ...[
                      TextFormField(
                        controller: _emailController,
                        keyboardType: TextInputType.emailAddress,
                        autofillHints: const [AutofillHints.email],
                        decoration: const InputDecoration(labelText: 'Email address'),
                        validator: _emailValidator,
                      ),
                      if (!_isForgotPassword) ...[
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _passwordController,
                          obscureText: true,
                          autofillHints: [_isRegistering ? AutofillHints.newPassword : AutofillHints.password],
                          decoration: const InputDecoration(labelText: 'Password'),
                          validator: (value) => (value?.isEmpty ?? true) ? 'Enter your password.' : null,
                        ),
                      ],
                    ],
                    if (_awaitingTwoFactor) ...[
                      SegmentedButton<bool>(
                        segments: const [
                          ButtonSegment(value: false, label: Text('Authenticator code')),
                          ButtonSegment(value: true, label: Text('Recovery code')),
                        ],
                        selected: {_useRecoveryCode},
                        onSelectionChanged: (value) => setState(() => _useRecoveryCode = value.first),
                      ),
                      const SizedBox(height: 13),
                      TextFormField(
                        controller: _useRecoveryCode ? _recoveryController : _codeController,
                        keyboardType: _useRecoveryCode ? TextInputType.text : TextInputType.number,
                        decoration: InputDecoration(labelText: _useRecoveryCode ? 'Recovery code' : '6-digit code'),
                        validator: (value) => (value?.trim().isEmpty ?? true) ? 'Enter the requested code.' : null,
                      ),
                    ],
                    if (_isRegistering && !_awaitingTwoFactor && !_isForgotPassword) ...[
                      const SizedBox(height: 13),
                      CheckboxListTile(
                        value: _acceptedTerms,
                        onChanged: (value) => setState(() => _acceptedTerms = value ?? false),
                        contentPadding: EdgeInsets.zero,
                        controlAffinity: ListTileControlAffinity.leading,
                        title: const Text('I agree to the Terms of Service and Privacy Policy.'),
                      ),
                    ],
                    const SizedBox(height: 18),
                    FilledButton(
                      onPressed: _isBusy ? null : _submit,
                      child: _isBusy
                          ? const SizedBox.square(dimension: 19, child: CircularProgressIndicator(strokeWidth: 2))
                          : Text(_isForgotPassword ? 'Send reset link' : _awaitingTwoFactor ? 'Verify and sign in' : _isRegistering ? 'Create account' : 'Log in'),
                    ),
                  ],
                ),
              ),
              if (!_isRegistering && !_awaitingTwoFactor && !_isForgotPassword)
                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () => setState(() {
                      _isForgotPassword = true;
                      _error = null;
                      _status = null;
                    }),
                    child: const Text('Forgot password?'),
                  ),
                ),
              if (_isForgotPassword || _isRegistering)
                TextButton(
                  onPressed: () => setState(() {
                    _isForgotPassword = false;
                    _isRegistering = !_isRegistering;
                    _error = null;
                    _status = null;
                  }),
                  child: Text(_isRegistering ? 'Already have an account? Log in' : 'Create a Tuklas account'),
                ),
              if (!_isRegistering && !_isForgotPassword && !_awaitingTwoFactor) ...[
                const SizedBox(height: 10),
                const _SocialDivider(),
                const SizedBox(height: 10),
                OutlinedButton.icon(
                  onPressed: () => widget.onSocialSignIn('google'),
                  icon: const Icon(Icons.g_mobiledata, size: 24),
                  label: const Text('Continue with Google'),
                ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  onPressed: () => widget.onSocialSignIn('facebook'),
                  icon: const Icon(Icons.facebook, size: 20, color: Color(0xFF1877F2)),
                  label: const Text('Continue with Facebook'),
                ),
              ],
              const SizedBox(height: 16),
              Text(
                'Tuklas is for youth aged 15 to 30. Guardian details are required for users under 18.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(color: colors.onSurfaceVariant),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String? _required(String? value) => (value?.trim().isEmpty ?? true) ? 'This field is required.' : null;

  String? _emailValidator(String? value) {
    final email = value?.trim() ?? '';
    if (email.isEmpty || !email.contains('@')) return 'Enter a valid email address.';
    return null;
  }
}

class TuklasVerificationScreen extends StatefulWidget {
  const TuklasVerificationScreen({super.key, required this.api, required this.user, required this.onSignOut});

  final TuklasApi api;
  final Map<String, dynamic> user;
  final VoidCallback onSignOut;

  @override
  State<TuklasVerificationScreen> createState() => _TuklasVerificationScreenState();
}

class TuklasSocialChallengeScreen extends StatefulWidget {
  const TuklasSocialChallengeScreen({
    super.key,
    required this.api,
    required this.ticket,
    required this.onAuthenticated,
    required this.onBack,
  });

  final TuklasApi api;
  final String ticket;
  final ValueChanged<Map<String, dynamic>> onAuthenticated;
  final VoidCallback onBack;

  @override
  State<TuklasSocialChallengeScreen> createState() => _TuklasSocialChallengeScreenState();
}

class _TuklasSocialChallengeScreenState extends State<TuklasSocialChallengeScreen> {
  final _code = TextEditingController();
  final _recovery = TextEditingController();
  bool _useRecovery = false;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _code.dispose();
    _recovery.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final result = await widget.api.exchangeSocialTicket(
        ticket: widget.ticket,
        deviceName: 'Tuklas mobile',
        code: _useRecovery ? null : _code.text.trim(),
        recoveryCode: _useRecovery ? _recovery.text.trim() : null,
      );
      widget.onAuthenticated(Map<String, dynamic>.from(result['user'] as Map));
    } catch (error) {
      if (mounted) setState(() => _error = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        appBar: AppBar(leading: IconButton(onPressed: widget.onBack, icon: const Icon(Icons.arrow_back)), title: const _TuklasWordmark()),
        body: Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 460),
            child: ListView(
              padding: const EdgeInsets.all(22),
              shrinkWrap: true,
              children: [
                const Text('SECURITY CHECK', style: TextStyle(color: TuklasColors.violet, fontSize: 11, fontWeight: FontWeight.w700)),
                const SizedBox(height: 8),
                Text('Confirm it’s you', style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 7),
                const Text('Your account uses two-factor authentication. Enter an authenticator code or a one-time recovery code.'),
                const SizedBox(height: 17),
                SegmentedButton<bool>(
                  segments: const [
                    ButtonSegment(value: false, label: Text('Authenticator')),
                    ButtonSegment(value: true, label: Text('Recovery')),
                  ],
                  selected: {_useRecovery},
                  onSelectionChanged: (value) => setState(() => _useRecovery = value.first),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: _useRecovery ? _recovery : _code,
                  keyboardType: _useRecovery ? TextInputType.text : TextInputType.number,
                  decoration: InputDecoration(labelText: _useRecovery ? 'Recovery code' : '6-digit code'),
                ),
                if (_error != null) ...[const SizedBox(height: 12), _MessageBanner(text: _error!, isError: true)],
                const SizedBox(height: 14),
                FilledButton(onPressed: _busy ? null : _verify, child: Text(_busy ? 'Verifying…' : 'Continue')),
              ],
            ),
          ),
        ),
      );
}

class _TuklasVerificationScreenState extends State<TuklasVerificationScreen> {
  bool _sending = false;
  String? _message;

  Future<void> _resend() async {
    setState(() => _sending = true);
    try {
      await widget.api.resendVerification();
      setState(() => _message = 'Verification email sent. Check your inbox, then sign in again.');
    } catch (error) {
      setState(() => _message = TuklasApi.errorMessage(error));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const _TuklasWordmark(), actions: [TextButton(onPressed: widget.onSignOut, child: const Text('Sign out'))]),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.mark_email_unread_outlined, size: 44, color: TuklasColors.violet),
              const SizedBox(height: 16),
              Text('Verify your email', style: Theme.of(context).textTheme.headlineSmall),
              const SizedBox(height: 8),
              Text('We sent a verification link to ${widget.user['email']}. Verify your address to open your workspace.', textAlign: TextAlign.center),
              const SizedBox(height: 14),
              if (_message != null) _MessageBanner(text: _message!, isError: false),
              FilledButton.icon(
                onPressed: _sending ? null : _resend,
                icon: const Icon(Icons.outgoing_mail),
                label: Text(_sending ? 'Sending…' : 'Resend verification email'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _LandingArtwork extends StatelessWidget {
  const _LandingArtwork();

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 255,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Positioned.fill(
            top: 8,
            left: 6,
            child: Transform.rotate(
              angle: -.04,
              child: DecoratedBox(
                decoration: BoxDecoration(color: TuklasColors.yellow, borderRadius: BorderRadius.circular(25)),
              ),
            ),
          ),
          Positioned.fill(
            top: 4,
            left: 3,
            child: Transform.rotate(
              angle: -.02,
              child: DecoratedBox(
                decoration: BoxDecoration(color: TuklasColors.pink, borderRadius: BorderRadius.circular(24)),
              ),
            ),
          ),
          Positioned.fill(
            child: ClipRRect(
              borderRadius: BorderRadius.circular(22),
              child: const CustomPaint(painter: _BrandCollagePainter()),
            ),
          ),
          Positioned(
            left: 18,
            bottom: 18,
            right: 75,
            child: Text(
              'Your future has\nmore than one path.',
              style: Theme.of(context).textTheme.titleLarge?.copyWith(color: Colors.white, fontWeight: FontWeight.w700, height: 1.05),
            ),
          ),
          const Positioned(right: 13, bottom: 16, child: Icon(Icons.explore, color: TuklasColors.yellow, size: 40)),
        ],
      ),
    );
  }
}

class _BrandCollagePainter extends CustomPainter {
  const _BrandCollagePainter();

  @override
  void paint(Canvas canvas, Size size) {
    canvas.drawColor(TuklasColors.deep, BlendMode.src);
    final center = Offset(size.width * .74, size.height * .35);
    final ring = Paint()
      ..color = Colors.white.withValues(alpha: .24)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 1;
    for (final radius in [34.0, 63.0, 92.0]) {
      canvas.drawCircle(center, radius, ring);
    }
    final trail = Path()
      ..moveTo(size.width * .12, size.height * .22)
      ..cubicTo(size.width * .32, size.height * .06, size.width * .57, size.height * .55, size.width * 1.1, size.height * .2);
    canvas.drawPath(trail, Paint()..color = Colors.white.withValues(alpha: .32)..style = PaintingStyle.stroke..strokeWidth = 1.5);
    canvas.drawCircle(Offset(size.width * .19, size.height * .28), 8, Paint()..color = TuklasColors.yellow);
    canvas.drawCircle(Offset(size.width * .54, size.height * .48), 5, Paint()..color = Colors.white);
    final pin = Path()
      ..moveTo(center.dx, center.dy + 35)
      ..cubicTo(center.dx - 35, center.dy - 8, center.dx - 20, center.dy - 42, center.dx, center.dy - 42)
      ..cubicTo(center.dx + 25, center.dy - 42, center.dx + 38, center.dy - 8, center.dx, center.dy + 35)
      ..close();
    canvas.drawShadow(pin, Colors.black.withValues(alpha: .22), 6, false);
    canvas.drawPath(pin, Paint()..color = TuklasColors.pink);
    canvas.drawCircle(center.translate(0, -12), 8, Paint()..color = TuklasColors.deep);
    final pencil = Path()
      ..moveTo(size.width * .46, size.height * .67)
      ..lineTo(size.width * .53, size.height * .38)
      ..lineTo(size.width * .57, size.height * .7)
      ..close();
    canvas.drawPath(pencil, Paint()..color = TuklasColors.yellow);
  }

  @override
  bool shouldRepaint(covariant _BrandCollagePainter oldDelegate) => false;
}

class _SectionHeading extends StatelessWidget {
  const _SectionHeading({required this.title, this.eyebrow});

  final String title;
  final String? eyebrow;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (eyebrow != null) ...[
          Text(eyebrow!.toUpperCase(), style: Theme.of(context).textTheme.labelSmall?.copyWith(color: TuklasColors.violet, fontWeight: FontWeight.w700)),
          const SizedBox(height: 4),
        ],
        Text(title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w500)),
      ],
    );
  }
}

class _InterestChips extends StatelessWidget {
  const _InterestChips();

  @override
  Widget build(BuildContext context) {
    const values = ['Electronics', 'Farming', 'Cooking', 'Computers', 'Sewing', 'Drawing', 'Baking', 'Caregiving', 'Welding'];
    const colors = [TuklasColors.yellow, TuklasColors.pink, Color(0xFFE7E5FF)];
    return Wrap(
      spacing: 7,
      runSpacing: 7,
      children: [
        for (var index = 0; index < values.length; index++)
          Chip(
            label: Text(values[index]),
            backgroundColor: colors[index % colors.length],
            side: BorderSide.none,
            visualDensity: VisualDensity.compact,
            labelStyle: const TextStyle(color: TuklasColors.ink, fontSize: 12),
          ),
      ],
    );
  }
}

class _PathwayRow extends StatelessWidget {
  const _PathwayRow({required this.number, required this.title, required this.color, this.planned = false});

  final String number;
  final String title;
  final Color color;
  final bool planned;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: CircleAvatar(
        backgroundColor: color,
        foregroundColor: color == TuklasColors.violet ? Colors.white : TuklasColors.ink,
        child: Text(number, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700)),
      ),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w500)),
      subtitle: planned ? const Text('Coming soon') : const Text('Build your profile'),
      trailing: planned ? null : const Icon(Icons.arrow_forward),
    );
  }
}

class _SocialDivider extends StatelessWidget {
  const _SocialDivider();

  @override
  Widget build(BuildContext context) => Row(children: [
        const Expanded(child: Divider()),
        Padding(padding: const EdgeInsets.symmetric(horizontal: 12), child: Text('or continue with', style: Theme.of(context).textTheme.bodySmall)),
        const Expanded(child: Divider()),
      ]);
}

class _MessageBanner extends StatelessWidget {
  const _MessageBanner({required this.text, required this.isError});

  final String text;
  final bool isError;

  @override
  Widget build(BuildContext context) {
    final color = isError ? Theme.of(context).colorScheme.error : TuklasColors.good;
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: .1),
        border: Border.all(color: color.withValues(alpha: .35)),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(text, style: TextStyle(color: color, height: 1.4)),
    );
  }
}

class _TuklasWordmark extends StatelessWidget {
  const _TuklasWordmark();

  @override
  Widget build(BuildContext context) => const Text(
        'tuklas',
        style: TextStyle(
          color: TuklasColors.ink,
          fontFamily: 'Schibsted Grotesk',
          fontSize: 25,
          fontWeight: FontWeight.w500,
          letterSpacing: -1.2,
        ),
      );
}

Future<void> openTuklasExternal(String url) async {
  final uri = Uri.tryParse(url);
  if (uri != null) await launchUrl(uri, mode: LaunchMode.externalApplication);
}
