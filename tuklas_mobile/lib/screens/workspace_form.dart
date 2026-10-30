import 'package:flutter/material.dart';

import '../api.dart';

class WorkspaceField {
  const WorkspaceField(
    this.name,
    this.label, {
    this.options,
    this.secret = false,
    this.multiline = false,
    this.required = false,
  });
  final String name, label;
  final List<String>? options;
  final bool secret, multiline, required;
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
        data[f.name] = f.name == 'is_active'
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
                  ? TextFormField(
                      controller: _inputs[f.name],
                      obscureText: f.secret,
                      maxLines: f.multiline ? 4 : 1,
                      decoration: InputDecoration(
                        labelText: f.label,
                        errorText: _errors[f.name],
                      ),
                      validator: (v) => f.required && (v ?? '').trim().isEmpty
                          ? 'Required'
                          : null,
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
                          DropdownMenuItem(value: o, child: Text(o)),
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
