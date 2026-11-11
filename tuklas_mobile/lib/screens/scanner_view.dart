import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
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
  final TextEditingController _goalController = TextEditingController();
  final ImagePicker _imagePicker = ImagePicker();
  final List<PlatformFile> _selectedFiles = [];
  Map<String, dynamic>? _latestDocument;
  late Future<List<dynamic>> _history;
  bool _consent = false;
  bool _scanning = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _history = ApiClient.instance.documentScans();
    _recoverCameraCapture();
  }

  @override
  void dispose() {
    _goalController.dispose();
    super.dispose();
  }

  Future<void> _pickDocument() async {
    setState(() => _error = null);
    FilePickerResult? result;
    try {
      result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
        allowMultiple: true,
        withData: true,
      );
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open your files. Please try again.');
      }
      return;
    }
    if (!mounted || result == null || result.files.isEmpty) return;

    final invalidFiles = result.files
        .where((file) => file.size > 10 * 1024 * 1024 || file.bytes == null)
        .toList();
    if (invalidFiles.isNotEmpty) {
      final invalid = invalidFiles.first;
      setState(
        () => _error = invalid.size > 10 * 1024 * 1024
            ? '${invalid.name} is over 10 MB.'
            : '${invalid.name} could not be read. Please choose it again.',
      );
      return;
    }
    setState(() {
      for (final file in result!.files) {
        final duplicate = _selectedFiles.any(
          (selected) =>
              selected.name == file.name && selected.size == file.size,
        );
        if (!duplicate && _selectedFiles.length < 5) {
          _selectedFiles.add(file);
        }
      }
      _error = _selectedFiles.length >= 5
          ? 'Up to five files can be scanned at a time.'
          : null;
    });
  }

  Future<void> _useCapturedPhoto(XFile photo) async {
    try {
      final bytes = await photo.readAsBytes();
      if (!mounted) {
        return;
      }
      if (bytes.length > 10 * 1024 * 1024) {
        setState(
          () => _error =
              'The photo is over 10 MB. Try again with better lighting.',
        );
        return;
      }

      setState(() {
        if (_selectedFiles.length >= 5) {
          _error = 'Up to five files can be scanned at a time.';
          return;
        }
        _selectedFiles.add(
          PlatformFile(
            name:
                'camera-document-${DateTime.now().millisecondsSinceEpoch}.jpg',
            size: bytes.length,
            bytes: bytes,
          ),
        );
        _error = null;
      });
    } catch (_) {
      if (mounted) {
        setState(
          () => _error =
              'The captured photo could not be read. Please try again.',
        );
      }
    }
  }

  Future<void> _takeDocumentPhoto() async {
    setState(() => _error = null);
    try {
      final photo = await _imagePicker.pickImage(
        source: ImageSource.camera,
        preferredCameraDevice: CameraDevice.rear,
        imageQuality: 88,
        maxWidth: 3000,
        maxHeight: 4000,
      );
      if (photo != null) {
        await _useCapturedPhoto(photo);
      }
    } catch (_) {
      if (mounted) {
        setState(() => _error = 'Could not open the camera. Please try again.');
      }
    }
  }

  Future<void> _recoverCameraCapture() async {
    if (kIsWeb || defaultTargetPlatform != TargetPlatform.android) {
      return;
    }
    try {
      final lostData = await _imagePicker.retrieveLostData();
      final files = lostData.files;
      if (files != null && files.isNotEmpty) {
        for (final file in files) {
          await _useCapturedPhoto(file);
        }
      }
    } catch (_) {
      if (mounted) {
        setState(
          () => _error = 'A previous camera photo could not be restored. Please take it again.',
        );
      }
    }
  }

  Future<void> _scan() async {
    if (_selectedFiles.isEmpty) {
      setState(() => _error = 'Choose a document first.');
      return;
    }
    if (!_consent) {
      setState(
        () => _error =
            'Please agree before sending the selected files to Gemini.',
      );
      return;
    }

    setState(() {
      _scanning = true;
      _error = null;
      _latestDocument = null;
    });
    final filesToScan = List<PlatformFile>.from(_selectedFiles);
    var scanned = 0;
    final failedFiles = <PlatformFile>[];
    String? firstFailure;
    for (var index = 0; index < filesToScan.length; index++) {
      final file = filesToScan[index];
      try {
        final document = await ApiClient.instance.uploadDocument(
          bytes: file.bytes!,
          filename: file.name,
          goal: _goalController.text,
        );
        if (!mounted) return;
        setState(() {
          _latestDocument = document;
          _selectedFiles.remove(file);
        });
        scanned += 1;
      } catch (error) {
        failedFiles.add(file);
        firstFailure ??= error is ApiException
            ? '${file.name}: ${error.message}'
            : '${file.name}: Could not connect to Tuklas. Check your internet connection and try again.';
        if (error is! ApiException || error.statusCode != 422) {
          failedFiles.addAll(filesToScan.skip(index + 1));
          break;
        }
      }
    }
    if (!mounted) return;
    setState(() {
      _selectedFiles
        ..clear()
        ..addAll(failedFiles);
      _consent = false;
      if (failedFiles.isEmpty) {
        _goalController.clear();
        _error = '$scanned ${scanned == 1 ? 'document' : 'documents'} scanned.';
      } else {
        _error = [
          firstFailure,
          '$scanned of ${filesToScan.length} scanned; ${failedFiles.length} remain. Fix the issue and retry the remaining files.',
        ].whereType<String>().join('\n');
      }
      _history = ApiClient.instance.documentScans();
    });
    if (scanned > 0) widget.onScanComplete();
    if (mounted) setState(() => _scanning = false);
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
                const TkPageHeading(
                  eyebrow: 'YOUR SKILLS, DISCOVERED',
                  title: 'Document scanner',
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
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: colors.warnBg,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          'Gemini is an AI and can get details or TESDA matches wrong. Check the result and confirm training and assessment requirements with TESDA or the provider.',
                          style: tkText(
                            context,
                            size: 12.5,
                            color: colors.warn,
                            height: 1.45,
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      SizedBox(
                        width: double.infinity,
                        child: OutlinedButton.icon(
                          onPressed: _scanning ? null : _pickDocument,
                          icon: const Icon(Icons.attach_file),
                          label: const Text('Choose multiple files or images'),
                        ),
                      ),
                      if (!kIsWeb &&
                          defaultTargetPlatform == TargetPlatform.android) ...[
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton.icon(
                            onPressed: _scanning ? null : _takeDocumentPhoto,
                            icon: const Icon(Icons.camera_alt_outlined),
                            label: const Text('Take document photo'),
                          ),
                        ),
                      ],
                      if (_selectedFiles.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        for (final file in _selectedFiles)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading:
                                file.bytes != null &&
                                    RegExp(
                                      r'\.(jpg|jpeg|png|webp)$',
                                      caseSensitive: false,
                                    ).hasMatch(file.name)
                                ? ClipRRect(
                                    borderRadius: BorderRadius.circular(8),
                                    child: Image.memory(
                                      file.bytes!,
                                      width: 48,
                                      height: 48,
                                      fit: BoxFit.cover,
                                    ),
                                  )
                                : const Icon(Icons.picture_as_pdf_outlined),
                            title: Text(
                              file.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                            subtitle: Text(
                              '${(file.size / (1024 * 1024)).toStringAsFixed(1)} MB',
                            ),
                            trailing: IconButton(
                              tooltip: 'Remove ${file.name}',
                              onPressed: _scanning
                                  ? null
                                  : () => setState(
                                      () => _selectedFiles.remove(file),
                                    ),
                              icon: const Icon(Icons.close),
                            ),
                          ),
                      ],
                      const SizedBox(height: 6),
                      Text(
                        'PDF or image · up to 10 MB each · up to 5 files per scan',
                        style: tkText(context, size: 12, color: colors.muted),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _goalController,
                        maxLength: 1000,
                        maxLines: 3,
                        minLines: 2,
                        enabled: !_scanning,
                        decoration: const InputDecoration(
                          labelText: 'What should Gemini focus on? (optional)',
                          hintText: 'Example: Match my certificate to a TESDA qualification and explain why.',
                          alignLabelWithHint: true,
                        ),
                      ),
                      const SizedBox(height: 6),
                      CheckboxListTile(
                        contentPadding: EdgeInsets.zero,
                        value: _consent,
                        onChanged: _scanning
                            ? null
                            : (value) =>
                                  setState(() => _consent = value ?? false),
                        title: Text(
                          'I agree to send the selected files to Google Gemini to scan them.',
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
                            _scanning
                                ? 'Scanning documents…'
                                : 'Scan ${_selectedFiles.length} document${_selectedFiles.length == 1 ? '' : 's'}',
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
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  message,
                                  style: tkText(
                                    context,
                                    size: 13,
                                    color: colors.bad,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                OutlinedButton.icon(
                                  onPressed: () => setState(
                                    () => _history = ApiClient.instance
                                        .documentScans(),
                                  ),
                                  icon: const Icon(Icons.refresh),
                                  label: const Text('Retry loading scans'),
                                ),
                              ],
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
    final reason = item['reason'];
    final description = item['description'];
    final source = item['source'] ?? item['site'];
    final details = <String>[
      if (tkSkillCoverageDetails(item).isNotEmpty) tkSkillCoverageDetails(item),
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
