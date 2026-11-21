import 'dart:math';

import 'package:flutter/material.dart';

import '../api.dart';

class WorkspaceField {
  const WorkspaceField(
    this.name,
    this.label, {
    this.options,
    this.optionLabels = const {},
    this.secret = false,
    this.multiline = false,
    this.required = false,
    this.passwordStrength = false,
    this.passwordConfirmation = false,
    this.minLength,
    this.maxLength,
  });
  final String name, label;
  final List<String>? options;
  final Map<String, String> optionLabels;
  final bool secret, multiline, required;
  final bool passwordStrength, passwordConfirmation;
  final int? minLength, maxLength;
}

String generateSecureInitialPassword() {
  final random = Random.secure();
  const lowercase = 'abcdefghijklmnopqrstuvwxyz';
  const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const numbers = '0123456789';
  const symbols = '!@#%^*-_';
  const allCharacters = '$lowercase$uppercase$numbers$symbols';
  final characters = <String>[
    lowercase[random.nextInt(lowercase.length)],
    uppercase[random.nextInt(uppercase.length)],
    numbers[random.nextInt(numbers.length)],
    symbols[random.nextInt(symbols.length)],
    for (var index = 0; index < 20; index++)
      allCharacters[random.nextInt(allCharacters.length)],
  ];

  for (var index = characters.length - 1; index > 0; index--) {
    final swapIndex = random.nextInt(index + 1);
    final character = characters[index];
    characters[index] = characters[swapIndex];
    characters[swapIndex] = character;
  }

  return characters.join();
}

class WorkspaceForm extends StatefulWidget {
  const WorkspaceForm({
    super.key,
    required this.title,
    required this.path,
    required this.fields,
    this.initial = const {},
    this.method = 'POST',
    this.auth = true,
  });
  final String title, path, method;
  final bool auth;
  final List<WorkspaceField> fields;
  final Map<String, dynamic> initial;
  @override
  State<WorkspaceForm> createState() => _WorkspaceFormState();
}

class _WorkspaceFormState extends State<WorkspaceForm> {
  final _form = GlobalKey<FormState>();
  late final Map<String, TextEditingController> _inputs = {
    for (final f in widget.fields)
      f.name: TextEditingController(text: '${widget.initial[f.name] ?? ''}'),
  };
  bool _saving = false;
  final Set<String> _visibleSecrets = {};
  String? _error;
  Map<String, String> _errors = {};
  @override
  void dispose() {
    for (final c in _inputs.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    if (!_form.currentState!.validate()) return;
    setState(() {
      _saving = true;
      _error = null;
      _errors = {};
    });
    try {
      final data = <String, dynamic>{};
      for (final f in widget.fields) {
        final value = _inputs[f.name]!.text;
        data[f.name] =
            f.name == 'is_active' ||
                f.name == 'is_verified' ||
                f.name == 'employer_verified'
            ? value == 'true'
            : value.isEmpty && !f.required
            ? null
            : value;
      }
      final response = await ApiClient.instance.workspace(
        widget.path,
        method: widget.method,
        data: data,
        auth: widget.auth,
      );
      if (!mounted) return;
      Navigator.pop(context, response);
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e is ApiException
            ? e.message
            : 'Could not save. Check your connection and try again.';
        if (e is ApiException) _errors = e.fieldErrors;
      });
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(widget.title)),
    body: Form(
      key: _form,
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          for (final f in widget.fields)
            Padding(
              padding: const EdgeInsets.only(bottom: 18),
              child: f.options == null
                  ? Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        TextFormField(
                          controller: _inputs[f.name],
                          obscureText:
                              f.secret && !_visibleSecrets.contains(f.name),
                          maxLines: f.multiline ? 4 : 1,
                          maxLength:
                              f.maxLength ??
                              (f.passwordStrength || f.passwordConfirmation
                                  ? 72
                                  : null),
                          decoration: InputDecoration(
                            labelText: f.label,
                            errorText: _errors[f.name],
                            suffixIcon: f.secret && f.name.contains('password')
                                ? IconButton(
                                    tooltip: _visibleSecrets.contains(f.name)
                                        ? 'Hide password'
                                        : 'Show password',
                                    onPressed: () => setState(() {
                                      if (!_visibleSecrets.add(f.name)) {
                                        _visibleSecrets.remove(f.name);
                                      }
                                    }),
                                    icon: Icon(
                                      _visibleSecrets.contains(f.name)
                                          ? Icons.visibility_off_outlined
                                          : Icons.visibility_outlined,
                                    ),
                                  )
                                : null,
                          ),
                          onChanged:
                              f.passwordStrength || f.passwordConfirmation
                              ? (_) => setState(() {})
                              : null,
                          validator: (value) {
                            final currentValue = value ?? '';
                            if (f.required && currentValue.trim().isEmpty) {
                              return 'Required';
                            }
                            if (f.minLength != null &&
                                currentValue.trim().length < f.minLength!) {
                              return 'Enter at least ${f.minLength} characters.';
                            }
                            if (f.passwordStrength &&
                                (_passwordStrength(currentValue) < 1 ||
                                    currentValue.length > 72)) {
                              return 'Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.';
                            }
                            if (f.passwordConfirmation &&
                                currentValue != _inputs['password']?.text) {
                              return 'Passwords do not match.';
                            }

                            return null;
                          },
                        ),
                        if (f.passwordStrength) ...[
                          const SizedBox(height: 8),
                          _passwordStrengthIndicator(
                            context,
                            _passwordStrength(_inputs[f.name]!.text),
                          ),
                        ],
                        if (f.passwordConfirmation &&
                            _inputs[f.name]!.text.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            _inputs[f.name]!.text == _inputs['password']?.text
                                ? 'Passwords match.'
                                : 'Passwords do not match.',
                            style: TextStyle(
                              color:
                                  _inputs[f.name]!.text ==
                                      _inputs['password']?.text
                                  ? Colors.green.shade700
                                  : Theme.of(context).colorScheme.error,
                            ),
                          ),
                        ],
                      ],
                    )
                  : DropdownButtonFormField<String>(
                      initialValue: f.options!.contains(_inputs[f.name]!.text)
                          ? _inputs[f.name]!.text
                          : null,
                      isExpanded: true,
                      decoration: InputDecoration(
                        labelText: f.label,
                        errorText: _errors[f.name],
                      ),
                      items: [
                        for (final o in f.options!)
                          DropdownMenuItem(
                            value: o,
                            child: Text(f.optionLabels[o] ?? o),
                          ),
                      ],
                      onChanged: _saving
                          ? null
                          : (v) => _inputs[f.name]!.text = v ?? '',
                      validator: (v) =>
                          f.required && v == null ? 'Required' : null,
                    ),
            ),
          if (_error != null)
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _saving ? null : _save,
            child: Text(_saving ? 'Saving…' : 'Save'),
          ),
        ],
      ),
    ),
  );

  Widget _passwordStrengthIndicator(BuildContext context, int strength) {
    const labels = ['Weak', 'Okay', 'Strong'];
    const messages = [
      'Weak. Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.',
      'Okay. It meets the password requirements.',
      'Strong. This password meets the requirements with extra length.',
    ];
    final colors = [
      Theme.of(context).colorScheme.error,
      Colors.orange.shade800,
      Colors.green.shade700,
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            const Text('Password strength'),
            Text(
              labels[strength],
              style: TextStyle(
                color: colors[strength],
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 5),
        LinearProgressIndicator(
          value: (strength + 1) / 3,
          color: colors[strength],
        ),
        const SizedBox(height: 5),
        Text(messages[strength]),
        const Text(
          'Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.',
        ),
      ],
    );
  }

  int _passwordStrength(String value) {
    final hasLowercase = RegExp(r'[a-z]').hasMatch(value);
    final hasUppercase = RegExp(r'[A-Z]').hasMatch(value);
    final hasNumber = RegExp(r'[0-9]').hasMatch(value);
    final hasSymbol = RegExp(r'[^a-zA-Z0-9]').hasMatch(value);
    if (value.length < 12 ||
        value.length > 72 ||
        !hasLowercase ||
        !hasUppercase ||
        !hasNumber ||
        !hasSymbol) {
      return 0;
    }

    return value.length >= 16 ? 2 : 1;
  }
}

Future<Map<String, dynamic>?> openWorkspaceForm(
  BuildContext context, {
  required String title,
  required String path,
  required List<WorkspaceField> fields,
  Map<String, dynamic> initial = const {},
  String method = 'POST',
  bool auth = true,
}) => Navigator.push<Map<String, dynamic>>(
  context,
  MaterialPageRoute(
    builder: (_) => WorkspaceForm(
      title: title,
      path: path,
      fields: fields,
      initial: initial,
      method: method,
      auth: auth,
    ),
  ),
);
