import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class ScannerView extends StatefulWidget {
  const ScannerView({super.key, required this.onScanComplete});

  final VoidCallback onScanComplete;

  @override
  State<ScannerView> createState() => _ScannerViewState();
}

class _ScannerViewState extends State<ScannerView> {
  PlatformFile? _selectedFile;
  Map<String, dynamic>? _latestDocument;
  late Future<List<dynamic>> _history;
  bool _consent = false;
  bool _scanning = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _history = ApiClient.instance.documentScans();
  }

  Future<void> _pickDocument() async {
    setState(() => _error = null);
    FilePickerResult? result;
    try {
      result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        allowMultiple: false,
        withData: true,
      );
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open your files. Please try again.');
      }
      return;
    }
    if (!mounted || result == null || result.files.isEmpty) return;

    final file = result.files.single;
    if (file.size > 10 * 1024 * 1024) {
      setState(() {
        _selectedFile = null;
        _error = 'Choose a file that is 10 MB or smaller.';
      });
      return;
    }
    if (file.bytes == null) {
      setState(() {
        _selectedFile = null;
        _error = 'The selected file could not be read. Please choose it again.';
      });
      return;
    }

    setState(() => _selectedFile = file);
  }

  Future<void> _scan() async {
    final file = _selectedFile;
    if (file == null) {
      setState(() => _error = 'Choose a document first.');
      return;
    }
    if (!_consent) {
      setState(
        () => _error = 'Please agree before sending the document to Gemini.',
      );
      return;
    }

    setState(() {
      _scanning = true;
      _error = null;
      _latestDocument = null;
    });
    try {
      final document = await ApiClient.instance.uploadDocument(
        bytes: file.bytes!,
        filename: file.name,
      );
      if (!mounted) return;
      setState(() {
        _latestDocument = document;
        _selectedFile = null;
        _consent = false;
        _history = ApiClient.instance.documentScans();
      });
      widget.onScanComplete();
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error is ApiException ? error.message : 'The scan could not be completed. Check your connection and try again.';
      });
      setState(() => _history = ApiClient.instance.documentScans());
    } finally {
      if (mounted) setState(() => _scanning = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final colors = TkColors.of(context);
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 32),
      children: [
        Center(
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 900),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Document scanner',
                  style: tkText(context, size: 27, weight: 500, spacing: -0.7),
                ),
                const SizedBox(height: 8),
                Text(
                  'Understand a document and find next steps.',
                  style: tkText(context, size: 15, color: colors.muted),
                ),
                const SizedBox(height: 18),
                TkCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Google Gemini AI',
                        style: tkText(context, size: 17, weight: 500),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Gemini summarizes documents, identifies covered skills and topics, and suggests related careers and training.',
                        style: tkText(
                          context,
                          size: 13.5,
                          color: colors.muted,
                          height: 1.45,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        'Your file is temporarily stored for the scan and sent to Google Gemini. Tuklas deletes the uploaded file after processing; your scan summary stays in your account.',
                        style: tkText(
                          context,
                          size: 12.5,
                          color: colors.ink2,
                          height: 1.45,
                        ),
                      ),
                      const SizedBox(height: 12),
                      const SizedBox(height: 18),
                      OutlinedButton.icon(
                        onPressed: _scanning ? null : _pickDocument,
                        icon: const Icon(Icons.attach_file),
                        label: Text(
                          _selectedFile == null
                              ? 'Choose a document'
                              : _selectedFile!.name,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'PDF or image · up to 10 MB · one document at a time',
                        style: tkText(context, size: 12, color: colors.muted),
                      ),
                      const SizedBox(height: 12),
                      CheckboxListTile(
                        contentPadding: EdgeInsets.zero,
                        value: _consent,
                        onChanged: _scanning
                            ? null
                            : (value) =>
                                  setState(() => _consent = value ?? false),
                        title: Text(
                          'I agree to send this file to Google Gemini to scan it.',
                          style: tkText(context, size: 13),
                        ),
                        controlAffinity: ListTileControlAffinity.leading,
                      ),
                      if (_error != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          _error!,
                          style: tkText(context, size: 13, color: colors.bad),
                        ),
                      ],
                      const SizedBox(height: 12),
                      SizedBox(
                        width: double.infinity,
                        child: FilledButton.icon(
                          onPressed: _scanning ? null : _scan,
                          icon: _scanning
                              ? const SizedBox(
                                  width: 18,
                                  height: 18,
                                  child: CircularProgressIndicator(
                                    strokeWidth: 2,
                                    color: Colors.white,
                                  ),
                                )
                              : const Icon(Icons.auto_awesome),
                          label: Text(
                            _scanning ? 'Scanning document…' : 'Scan document',
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                if (_latestDocument != null) ...[
                  const SizedBox(height: 16),
                  _analysisCard(
                    context,
                    '${_latestDocument!['original_name'] ?? 'Latest scan'}',
                    asMap(_latestDocument!['analysis']),
                  ),
                ],
                const SizedBox(height: 16),
                TkCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Your scans',
                        style: tkText(context, size: 17, weight: 500),
                      ),
                      const SizedBox(height: 12),
                      FutureBuilder<List<dynamic>>(
                        future: _history,
                        builder: (context, snapshot) {
                          if (snapshot.connectionState !=
                              ConnectionState.done) {
                            return const Center(
                              child: Padding(
                                padding: EdgeInsets.all(18),
                                child: CircularProgressIndicator(),
                              ),
                            );
                          }
                          if (snapshot.hasError) {
                            final message = snapshot.error is ApiException
                                ? (snapshot.error as ApiException).message
                                : 'Could not load your scan history.';
                            return Text(
                              message,
                              style: tkText(
                                context,
                                size: 13,
                                color: colors.bad,
                              ),
                            );
                          }
                          final documents = snapshot.data ?? [];
                          if (documents.isEmpty) {
                            return Text(
                              'Your completed scans will appear here.',
                              style: tkText(
                                context,
                                size: 13,
                                color: colors.muted,
                              ),
                            );
                          }
                          return Column(
                            children: [
                              for (final raw in documents)
                                _historyItem(context, asMap(raw)),
                            ],
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _historyItem(BuildContext context, Map<String, dynamic> document) {
    final analysis = asMap(document['analysis']);
    final status = '${document['status'] ?? 'processing'}';
    final tone = status == 'completed'
        ? 'good'
        : status == 'failed'
        ? 'bad'
        : 'warn';

    return ExpansionTile(
      tilePadding: EdgeInsets.zero,
      title: Text(
        '${document['original_name'] ?? 'Document'}',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: tkText(context, size: 14, weight: 500),
      ),
      subtitle: Padding(
        padding: const EdgeInsets.only(top: 4),
        child: TkChip(label: status, tone: tone),
      ),
      children: [
        if (document['failure_message'] != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Text(
              '${document['failure_message']}',
              style: tkText(context, size: 13, color: TkColors.of(context).bad),
            ),
          ),
        if (analysis.isNotEmpty)
          Align(
            alignment: Alignment.centerLeft,
            child: _analysisContent(context, analysis),
          ),
      ],
    );
  }

  Widget _analysisCard(
    BuildContext context,
    String title,
    Map<String, dynamic> analysis,
  ) {
    return TkCard(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: tkText(context, size: 16, weight: 500)),
          const SizedBox(height: 12),
          _analysisContent(context, analysis),
        ],
      ),
    );
  }

  Widget _analysisContent(BuildContext context, Map<String, dynamic> analysis) {
    final sections = <(String, String)>[
      ('summary', 'Summary'),
      (
        analysis.containsKey('skillsDetected') ? 'skillsDetected' : 'skills',
        'Skills found',
      ),
      (
        analysis.containsKey('careerMatches') ? 'careerMatches' : 'job_roles',
        'Career matches',
      ),
      ('jobRecommendations', 'Job recommendations'),
      ('skillGaps', 'Skills to build'),
      (
        analysis.containsKey('tesdaRecommendations')
            ? 'tesdaRecommendations'
            : 'tesda_training',
        'TESDA training to consider',
      ),
      ('learningRecommendations', 'Learning recommendations'),
      (
        analysis.containsKey('nextActions') ? 'nextActions' : 'next_steps',
        'Next steps',
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final (key, title) in sections) ...[
          if (analysis[key] != null && analysis[key] != '') ...[
            Padding(
              padding: const EdgeInsets.only(top: 8, bottom: 3),
              child: Text(
                title,
                style: tkText(context, size: 13.5, weight: 500),
              ),
            ),
            if (analysis[key] is List)
              for (final item in asList(analysis[key]))
                Padding(
                  padding: const EdgeInsets.only(left: 4, top: 2, bottom: 2),
                  child: _analysisItem(context, item),
                )
            else
              Text('${analysis[key]}', style: tkText(context, size: 13)),
          ],
        ],
      ],
    );
  }

  String _analysisItemText(dynamic item) {
    if (item is! Map) return '$item';

    final title = item['title'] ?? item['name'] ?? 'Recommendation';
    final match = item['match'];
    final reason = item['reason'];
    final description = item['description'];
    final source = item['source'] ?? item['site'];
    final details = <String>[
      if (match != null && '$match'.isNotEmpty) '$match match',
      if (reason != null && '$reason'.isNotEmpty) '$reason',
      if (description != null && '$description'.isNotEmpty) '$description',
      if (source != null && '$source'.isNotEmpty) '$source',
    ];

    return details.isEmpty ? '$title' : '$title — ${details.join(' · ')}';
  }

  Widget _analysisItem(BuildContext context, dynamic item) {
    final url = item is Map ? item['directUrl'] ?? item['url'] : null;
    final uri = url is String ? Uri.tryParse(url) : null;
    final resourceUri =
        uri != null && uri.scheme == 'https' && uri.host.isNotEmpty
        ? uri
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('• ${_analysisItemText(item)}', style: tkText(context, size: 13)),
        if (resourceUri != null)
          TextButton.icon(
            onPressed: () async {
              final opened = await launchUrl(
                resourceUri,
                mode: LaunchMode.externalApplication,
              );
              if (!opened && context.mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Could not open this resource.'),
                  ),
                );
              }
            },
            icon: const Icon(Icons.open_in_new, size: 14),
            label: const Text('Open resource'),
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              visualDensity: VisualDensity.compact,
            ),
          ),
      ],
    );
  }
}
