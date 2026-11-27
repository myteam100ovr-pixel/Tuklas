import 'dart:async';

import 'package:file_picker/file_picker.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../theme.dart';
import '../ui.dart';

class ScannerView extends StatefulWidget {
  const ScannerView({super.key, required this.onScanComplete, this.onOpenLink});

  final VoidCallback onScanComplete;
  final ValueChanged<String>? onOpenLink;

  @override
  State<ScannerView> createState() => _ScannerViewState();
}

class _ScannerViewState extends State<ScannerView> {
  final GlobalKey _selectedFilesKey = GlobalKey();
  final TextEditingController _goalController = TextEditingController();
  final ImagePicker _imagePicker = ImagePicker();
  final List<PlatformFile> _selectedFiles = [];
  Map<String, dynamic>? _latestDocument;
  late Future<List<dynamic>> _history;
  bool _consent = false;
  bool _scanning = false;
  bool _cancelRequested = false;
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
        allowedExtensions: const ['pdf', 'docx', 'jpg', 'jpeg', 'png', 'webp'],
        allowMultiple: true,
        withData: true,
      );
    } catch (error) {
      if (mounted) {
        final denied = _isPermissionDenied(error);
        setState(
          () => _error = denied
              ? tkL10n(context).fileAccessDenied
              : tkL10n(context).couldNotOpenFiles,
        );
        await _recordOutcome(denied ? 'denied' : 'error', 'File picker');
        _showToast(_error!, isError: true);
      }
      return;
    }
    if (!mounted) return;
    if (result == null || result.files.isEmpty) {
      await _recordOutcome('canceled', 'File selection');
      if (!mounted) return;
      _showToast(tkL10n(context).fileSelectionCanceled);
      return;
    }

    final invalidFiles = result.files
        .where((file) => file.size > 10 * 1024 * 1024 || file.bytes == null)
        .toList();
    if (invalidFiles.isNotEmpty) {
      final invalid = invalidFiles.first;
      setState(
        () => _error = invalid.size > 10 * 1024 * 1024
            ? tkL10n(context).fileOverTenMb(invalid.name)
            : tkL10n(context).fileCouldNotRead(invalid.name),
      );
      return;
    }
    setState(() {
      for (final file in result!.files) {
        final duplicate = _selectedFiles.any(
          (selected) =>
              (selected.name == file.name && selected.size == file.size) ||
              (selected.bytes != null &&
                  file.bytes != null &&
                  listEquals(selected.bytes, file.bytes)),
        );
        if (!duplicate && _selectedFiles.length < 5) {
          _selectedFiles.add(file);
        }
      }
      _error = _selectedFiles.length >= 5 ? tkL10n(context).maxFiveScans : null;
    });
  }

  Future<void> _replaceDocument(int index) async {
    setState(() => _error = null);
    try {
      final result = await FilePicker.pickFiles(
        type: FileType.custom,
        allowedExtensions: const ['pdf', 'docx', 'jpg', 'jpeg', 'png', 'webp'],
        allowMultiple: false,
        withData: true,
      );
      if (!mounted || result == null || result.files.isEmpty) return;

      final file = result.files.first;
      if (file.size > 10 * 1024 * 1024 || file.bytes == null) {
        setState(
          () => _error = file.size > 10 * 1024 * 1024
              ? tkL10n(context).fileOverTenMb(file.name)
              : tkL10n(context).fileCouldNotRead(file.name),
        );
        return;
      }
      final duplicate = _selectedFiles.asMap().entries.any(
        (entry) =>
            entry.key != index &&
            ((entry.value.name == file.name && entry.value.size == file.size) ||
                (entry.value.bytes != null &&
                    listEquals(entry.value.bytes, file.bytes))),
      );
      if (duplicate) {
        setState(() => _error = tkL10n(context).fileAlreadyInScanList);
        return;
      }
      setState(() {
        if (index < _selectedFiles.length) {
          _selectedFiles[index] = file;
        }
        _error = null;
      });
    } catch (error) {
      if (mounted) {
        setState(
          () => _error = _isPermissionDenied(error)
              ? tkL10n(context).fileAccessDenied
              : tkL10n(context).couldNotOpenFiles,
        );
      }
    }
  }

  Future<void> _useCapturedPhoto(XFile photo, {int? replaceIndex}) async {
    try {
      final bytes = await photo.readAsBytes();
      if (!mounted) {
        return;
      }
      if (bytes.length > 10 * 1024 * 1024) {
        setState(() => _error = tkL10n(context).photoOverTenMb);
        _showToast(_error!, isError: true);
        return;
      }

      var photoAdded = false;
      setState(() {
        if (replaceIndex == null && _selectedFiles.length >= 5) {
          _error = tkL10n(context).maxFiveScans;
          return;
        }
        final isDuplicate = _selectedFiles.indexed.any(
          (entry) =>
              entry.$1 != replaceIndex &&
              entry.$2.bytes != null &&
              listEquals(entry.$2.bytes, bytes),
        );
        if (isDuplicate) {
          _error = tkL10n(context).photoAlreadyInScanList;
          return;
        }
        final capturedFile = PlatformFile(
          name: 'camera-document-${DateTime.now().millisecondsSinceEpoch}.jpg',
          size: bytes.length,
          bytes: bytes,
        );
        if (replaceIndex != null && replaceIndex < _selectedFiles.length) {
          _selectedFiles[replaceIndex] = capturedFile;
        } else {
          _selectedFiles.add(capturedFile);
        }
        _error = null;
        photoAdded = true;
      });
      if (!photoAdded) {
        _showToast(_error ?? tkL10n(context).photoCouldNotAdd, isError: true);
        return;
      }

      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        final queueContext = _selectedFilesKey.currentContext;
        if (queueContext != null) {
          unawaited(
            Scrollable.ensureVisible(
              queueContext,
              alignment: 0.25,
              duration: const Duration(milliseconds: 180),
            ),
          );
        }
      });
      _showToast(
        replaceIndex == null
            ? tkL10n(context).photoAddedReady
            : tkL10n(context).photoAddedReplacement,
      );
    } catch (_) {
      if (mounted) {
        await _recordOutcome('error', photo.name);
        setState(() => _error = tkL10n(context).capturedPhotoUnreadable);
        _showToast(_error!, isError: true);
      }
    }
  }

  Future<void> _takeDocumentPhoto({int? replaceIndex}) async {
    setState(() => _error = null);
    try {
      final photo = await _imagePicker.pickImage(
        source: ImageSource.camera,
        preferredCameraDevice: CameraDevice.rear,
        imageQuality: 80,
        maxWidth: 2048,
        maxHeight: 2880,
      );
      if (photo != null) {
        await _useCapturedPhoto(photo, replaceIndex: replaceIndex);
      } else {
        await _recordOutcome('canceled', 'Camera capture');
        if (!mounted) return;
        _showToast(tkL10n(context).cameraCaptureCanceled);
      }
    } catch (error) {
      if (mounted) {
        final denied = _isPermissionDenied(error);
        setState(
          () => _error = denied
              ? tkL10n(context).cameraAccessDenied
              : tkL10n(context).cameraCouldNotOpen,
        );
        await _recordOutcome(denied ? 'denied' : 'error', 'Camera');
        _showToast(_error!, isError: true);
      }
    }
  }

  bool _isPermissionDenied(Object error) =>
      error.toString().toLowerCase().contains('denied') ||
      (error is PlatformException &&
          error.code.toLowerCase().contains('permission'));

  Future<void> _recordOutcome(String status, String fileName) async {
    try {
      await ApiClient.instance.reportDocumentScanOutcome(
        status: status,
        fileName: fileName,
      );
    } catch (_) {
      // Keep local feedback visible if notification sync is offline.
    }
  }

  Widget _selectedFileTile(int index) {
    final file = _selectedFiles[index];
    final isImage = RegExp(
      r'\.(jpg|jpeg|png|webp)$',
      caseSensitive: false,
    ).hasMatch(file.name);

    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: isImage && file.bytes != null
          ? ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.memory(
                file.bytes!,
                width: 48,
                height: 48,
                cacheWidth: 120,
                cacheHeight: 120,
                fit: BoxFit.cover,
              ),
            )
          : const Icon(Icons.description_outlined),
      title: Text(file.name, maxLines: 1, overflow: TextOverflow.ellipsis),
      subtitle: Text('${(file.size / (1024 * 1024)).toStringAsFixed(1)} MB'),
      trailing: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          PopupMenuButton<String>(
            tooltip: tkL10n(context).replaceFile(file.name),
            enabled: !_scanning,
            onSelected: (choice) {
              if (choice == 'camera') {
                _takeDocumentPhoto(replaceIndex: index);
              } else {
                _replaceDocument(index);
              }
            },
            itemBuilder: (context) => [
              PopupMenuItem(
                value: 'file',
                child: Text(tkL10n(context).chooseReplacementFile),
              ),
              if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android)
                PopupMenuItem(
                  value: 'camera',
                  child: Text(tkL10n(context).takeReplacementPhoto),
                ),
            ],
            icon: const Icon(Icons.edit_outlined),
          ),
          IconButton(
            tooltip: tkL10n(context).removeFile(file.name),
            onPressed: _scanning
                ? null
                : () => setState(() => _selectedFiles.removeAt(index)),
            icon: const Icon(Icons.close),
          ),
        ],
      ),
    );
  }

  void _showToast(String message, {bool isError = false}) {
    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        content: Text(message),
        duration: const Duration(seconds: 5),
        backgroundColor: isError ? TkColors.of(context).bad : null,
      ),
    );
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
        setState(() => _error = tkL10n(context).previousCameraPhotoNotRestored);
      }
    }
  }

  Future<void> _scan() async {
    if (_scanning) return;
    if (_selectedFiles.isEmpty) {
      setState(() => _error = tkL10n(context).chooseDocumentFirst);
      return;
    }
    if (!_consent) {
      setState(() => _error = tkL10n(context).agreeBeforeGeminiScan);
      return;
    }

    setState(() {
      _scanning = true;
      _cancelRequested = false;
      _error = null;
      _latestDocument = null;
    });
    final filesToScan = List<PlatformFile>.from(_selectedFiles);
    var scanned = 0;
    var reusedRecentScan = false;
    final failedFiles = <PlatformFile>[];
    String? firstFailure;
    for (var index = 0; index < filesToScan.length; index++) {
      if (_cancelRequested) {
        failedFiles.addAll(filesToScan.skip(index));
        break;
      }
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
        reusedRecentScan = reusedRecentScan || document['_duplicate'] == true;
        scanned += 1;
      } catch (error) {
        failedFiles.add(file);
        final denied =
            error is ApiException &&
            (error.statusCode == 403 || error.statusCode == 422);
        await _recordOutcome(denied ? 'denied' : 'error', file.name);
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
        _error = tkL10n(context).scanDocumentsCount(scanned);
      } else {
        _error = [
          firstFailure,
          tkL10n(context)
              .scansRemain(scanned, filesToScan.length, failedFiles.length),
        ].whereType<String>().join('\n');
      }
      _history = ApiClient.instance.documentScans();
    });
    if (_cancelRequested && failedFiles.isNotEmpty) {
      await _recordOutcome(
        'canceled',
        tkL10n(context).remainingDocuments(failedFiles.length),
      );
      if (!mounted) return;
      _showToast(tkL10n(context).documentScanCanceled);
    } else if (failedFiles.isNotEmpty) {
      _showToast(tkL10n(context).someScansFailed, isError: true);
    } else if (scanned > 0) {
      _showToast(
        reusedRecentScan
            ? tkL10n(context).duplicateScanReused
            : tkL10n(context).documentsScannedSuccessfully(scanned),
      );
    }
    if (scanned > 0) widget.onScanComplete();
    if (mounted) setState(() => _scanning = false);
  }

  Widget _scanStep(
    BuildContext context,
    int number,
    String title,
    String detail,
  ) {
    final colors = TkColors.of(context);

    return Padding(
      padding: const EdgeInsets.only(top: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 26,
            height: 26,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: TkColors.violet.withValues(alpha: .12),
              shape: BoxShape.circle,
            ),
            child: Text(
              '$number',
              style: tkText(
                context,
                size: 12,
                weight: 600,
                color: colors.taskMeta,
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: tkText(context, size: 13.5, weight: 500)),
                const SizedBox(height: 2),
                Text(
                  detail,
                  style: tkText(
                    context,
                    size: 12,
                    color: colors.muted,
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
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
                TkPageHeading(
                  eyebrow: tkL10n(context).scannerEyebrow,
                  title: tkL10n(context).documentScanner,
                ),
                const SizedBox(height: 8),
                Text(
                  tkL10n(context).understandDocumentNextSteps,
                  style: tkText(context, size: 15, color: colors.muted),
                ),
                const SizedBox(height: 18),
                TkCard(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 8,
                    vertical: 2,
                  ),
                  child: ExpansionTile(
                    initiallyExpanded: false,
                    tilePadding: const EdgeInsets.symmetric(horizontal: 10),
                    childrenPadding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                    backgroundColor: colors.surface,
                    collapsedBackgroundColor: colors.surface,
                    iconColor: colors.taskMeta,
                    collapsedIconColor: colors.muted,
                    textColor: colors.ink,
                    collapsedTextColor: colors.ink,
                    expansionAnimationStyle: motionController.enabled
                        ? AnimationStyle.noAnimation
                        : null,
                    title: Text(
                      tkL10n(context).howToUseScanner,
                      style: tkText(context, size: 14, weight: 500),
                    ),
                    children: [
                      _scanStep(
                        context,
                        1,
                        tkL10n(context).chooseYourDocument,
                        tkL10n(context).scannerChooseDocumentLong,
                      ),
                      _scanStep(
                        context,
                        2,
                        tkL10n(context).addGoalOptional,
                        tkL10n(context).scannerGoalDescription,
                      ),
                      _scanStep(
                        context,
                        3,
                        tkL10n(context).reviewAndAgree,
                        tkL10n(context).scannerReviewDescription,
                      ),
                      _scanStep(
                        context,
                        4,
                        tkL10n(context).scanReviewNextSteps,
                        tkL10n(context).scannerReviewResultsDescription,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                TkCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tkL10n(context).geminiAi,
                        style: tkText(context, size: 17, weight: 500),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        tkL10n(context).geminiScannerDescription,
                        style: tkText(
                          context,
                          size: 13.5,
                          color: colors.muted,
                          height: 1.45,
                        ),
                      ),
                      const SizedBox(height: 12),
                      ExpansionTile(
                        initiallyExpanded: false,
                        tilePadding: EdgeInsets.zero,
                        childrenPadding: const EdgeInsets.only(bottom: 8),
                        backgroundColor: colors.surface,
                        collapsedBackgroundColor: colors.surface,
                        iconColor: colors.taskMeta,
                        collapsedIconColor: colors.muted,
                        textColor: colors.ink,
                        collapsedTextColor: colors.ink,
                        expansionAnimationStyle: motionController.enabled
                            ? AnimationStyle.noAnimation
                            : null,
                        title: Text(
                          tkL10n(context).scannerPrivacyDetails,
                          style: tkText(context, size: 13, weight: 500),
                        ),
                        children: [
                          Text(
                            tkL10n(context).scannerPrivacyLong,
                            style: tkText(
                              context,
                              size: 12.5,
                              color: colors.ink2,
                              height: 1.45,
                            ),
                          ),
                        ],
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
                          tkL10n(context).scannerAiNotice,
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
                          label: Text(tkL10n(context).chooseMultipleFiles),
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
                            label: Text(tkL10n(context).takeDocumentPhoto),
                          ),
                        ),
                      ],
                      if (_selectedFiles.isNotEmpty) ...[
                        const SizedBox(height: 8),
                        Container(
                          key: _selectedFilesKey,
                          width: double.infinity,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 8,
                          ),
                          decoration: BoxDecoration(
                            color: colors.surface2,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: colors.line),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Padding(
                                padding: const EdgeInsets.only(bottom: 4),
                                child: Row(
                                  children: [
                                    Icon(
                                      Icons.fact_check_outlined,
                                      size: 18,
                                      color: colors.taskMeta,
                                    ),
                                    const SizedBox(width: 8),
                                    Expanded(
                                      child: Text(
                                        tkL10n(context).readyToScan,
                                        style: tkText(
                                          context,
                                          size: 13,
                                          weight: 600,
                                        ),
                                      ),
                                    ),
                                    Text(
                                      '${_selectedFiles.length}/5',
                                      style: tkText(
                                        context,
                                        size: 12,
                                        color: colors.muted,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              for (
                                var index = 0;
                                index < _selectedFiles.length;
                                index++
                              )
                                _selectedFileTile(index),
                            ],
                          ),
                        ),
                      ],
                      const SizedBox(height: 6),
                      Text(
                        tkL10n(context).scanFileLimits,
                        style: tkText(context, size: 12, color: colors.muted),
                      ),
                      const SizedBox(height: 12),
                      TextField(
                        controller: _goalController,
                        maxLength: 1000,
                        maxLines: 3,
                        minLines: 2,
                        enabled: !_scanning,
                        decoration: InputDecoration(
                          labelText: tkL10n(context).geminiFocusOptional,
                          hintText: tkL10n(context).scannerExamplePrompt,
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
                          tkL10n(context).agreeSendFilesToGemini,
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
                                ? tkL10n(context).scanningDocuments
                                : tkL10n(context)
                                      .scanDocumentCount(_selectedFiles.length),
                          ),
                        ),
                      ),
                      if (_scanning) ...[
                        const SizedBox(height: 8),
                        SizedBox(
                          width: double.infinity,
                          child: OutlinedButton(
                            onPressed: _cancelRequested
                                ? null
                                : () => setState(() {
                                    _cancelRequested = true;
                                    _error = tkL10n(context)
                                        .cancelingCurrentDocument;
                                  }),
                            child: Text(
                              _cancelRequested
                                  ? tkL10n(context).canceling
                                  : tkL10n(context).cancelRemainingScans,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                if (_latestDocument != null) ...[
                  const SizedBox(height: 16),
                  _analysisCard(
                    context,
                    '${_latestDocument!['original_name'] ?? tkL10n(context).latestScan}',
                    asMap(_latestDocument!['analysis']),
                  ),
                ],
                const SizedBox(height: 16),
                TkCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        tkL10n(context).yourScans,
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
                                : tkL10n(context).couldNotLoadScanHistory;
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
                                  label: Text(
                                    tkL10n(context).retryLoadingScans,
                                  ),
                                ),
                              ],
                            );
                          }
                          final documents = snapshot.data ?? [];
                          if (documents.isEmpty) {
                            return Text(
                              tkL10n(context).completedScansEmpty,
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
    final l10n = tkL10n(context);
    final tone = status == 'completed'
        ? 'good'
        : status == 'failed'
        ? 'bad'
        : 'warn';

    return ExpansionTile(
      backgroundColor: TkColors.of(context).surface,
      collapsedBackgroundColor: TkColors.of(context).surface,
      iconColor: TkColors.of(context).taskMeta,
      collapsedIconColor: TkColors.of(context).muted,
      textColor: TkColors.of(context).ink,
      collapsedTextColor: TkColors.of(context).ink,
      expansionAnimationStyle: motionController.enabled
          ? AnimationStyle.noAnimation
          : null,
      tilePadding: EdgeInsets.zero,
      title: Text(
        '${document['original_name'] ?? l10n.document}',
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        style: tkText(context, size: 14, weight: 500),
      ),
      subtitle: Padding(
        padding: const EdgeInsets.only(top: 4),
        child: TkChip(
          label: switch (status) {
            'completed' => l10n.scanCompleted,
            'failed' => l10n.scanFailed,
            _ => l10n.scanProcessing,
          },
          tone: tone,
        ),
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
    final l10n = tkL10n(context);
    final sections = <(String, String)>[
      ('summary', l10n.scanSummary),
      (
        analysis.containsKey('skillsDetected') ? 'skillsDetected' : 'skills',
        l10n.skillsFound,
      ),
      (
        analysis.containsKey('careerMatches') ? 'careerMatches' : 'job_roles',
        l10n.careerMatches,
      ),
      ('jobRecommendations', l10n.jobRecommendations),
      ('skillGaps', l10n.skillsToBuild),
      (
        analysis.containsKey('tesdaRecommendations')
            ? 'tesdaRecommendations'
            : 'tesda_training',
        l10n.tesdaTrainingConsider,
      ),
      ('learningRecommendations', l10n.learningRecommendations),
      (
        analysis.containsKey('nextActions') ? 'nextActions' : 'next_steps',
        l10n.nextSteps,
      ),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (analysis['keyFacts'] is List &&
            asList(analysis['keyFacts']).isNotEmpty) ...[
          Text(
            l10n.keyDetails,
            style: tkText(context, size: 13.5, weight: 500),
          ),
          const SizedBox(height: 4),
          for (final fact in asList(analysis['keyFacts']))
            if (fact is Map && fact['label'] != null && fact['value'] != null)
              Padding(
                padding: const EdgeInsets.only(left: 4, top: 2, bottom: 4),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${fact['label']}: ${fact['value']}',
                      style: tkText(context, size: 12.5),
                    ),
                    if (fact['evidence'] is String &&
                        (fact['evidence'] as String).trim().isNotEmpty)
                      Text(
                        '${l10n.documentEvidenceLabel}: ${fact['evidence']}',
                        style: tkText(
                          context,
                          size: 11.5,
                          color: TkColors.of(context).muted,
                        ),
                      ),
                  ],
                ),
              ),
        ],
        if (analysis['extractedFields'] is Map &&
            asMap(analysis['extractedFields']).values
                .any((value) => value != null && value != '')) ...[
          Text(
            l10n.readableDocumentDetails,
            style: tkText(context, size: 13.5, weight: 500),
          ),
          const SizedBox(height: 4),
          for (final entry in asMap(analysis['extractedFields']).entries)
            Padding(
              padding: const EdgeInsets.only(left: 4, top: 2, bottom: 2),
              child: Text(
                '${_fieldLabel(entry.key)}: ${entry.value == null
                    ? l10n.notReadableOrShown
                    : entry.value == true
                    ? 'Yes'
                    : entry.value == false
                    ? 'No'
                    : entry.value}',
                style: tkText(context, size: 12.5),
              ),
            ),
        ],
        for (final (key, label) in <(String, String)>[
          ('identityChecks', l10n.identityCrossCheck),
          ('requirementChecks', l10n.requirementCrossCheck),
        ])
          if (analysis[key] is List && asList(analysis[key]).isNotEmpty) ...[
            Padding(
              padding: const EdgeInsets.only(top: 8, bottom: 3),
              child: Text(
                label,
                style: tkText(context, size: 13.5, weight: 500),
              ),
            ),
            for (final check in asList(analysis[key]))
              Padding(
                padding: const EdgeInsets.only(left: 4, top: 2, bottom: 2),
                child: Text(
                  _checkText(check),
                  style: tkText(
                    context,
                    size: 12.5,
                    color: TkColors.of(context).muted,
                  ),
                ),
              ),
          ],
        if (analysis['recommended_links'] is List &&
            asList(analysis['recommended_links']).isNotEmpty) ...[
          Text(
            l10n.openInTuklas,
            style: tkText(context, size: 13.5, weight: 500),
          ),
          const SizedBox(height: 6),
          Wrap(
            spacing: 6,
            runSpacing: 4,
            children: [
              for (final item in asList(analysis['recommended_links']))
                if (item is Map &&
                    item['mobile_route'] is String &&
                    (item['mobile_route'] as String).startsWith('/') &&
                    !(item['mobile_route'] as String).startsWith('//'))
                  ActionChip(
                    avatar: const Icon(
                      Icons.open_in_new,
                      size: 15,
                      color: TkColors.violet,
                    ),
                    label: Text('${item['label'] ?? l10n.openPage}'),
                    onPressed: widget.onOpenLink == null
                        ? null
                        : () => widget.onOpenLink!(
                            item['mobile_route'] as String,
                          ),
                  ),
            ],
          ),
          const SizedBox(height: 6),
        ],
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

  String _fieldLabel(String key) => switch (key) {
    'fullName' => tkL10n(context).name,
    'issuingOrganization' => tkL10n(context).issuingOrganization,
    'issueDate' => tkL10n(context).issueDate,
    'expiryDate' => tkL10n(context).expiryDate,
    'signaturePresent' => tkL10n(context).signatureVisible,
    'qualification' => tkL10n(context).qualification,
    _ => key,
  };

  String _checkText(dynamic check) {
    if (check is! Map) return '$check';
    final subject =
        check['field'] ?? check['requirement'] ?? tkL10n(context).checkLabel;
    final details = [
      check['status'],
      check['reason'],
      check['evidence'],
    ].where((value) => value != null && '$value'.trim().isNotEmpty).join(' · ');
    return '$subject: ${details.isEmpty ? tkL10n(context).noReadableComparison : details}';
  }

  String _analysisItemText(dynamic item) {
    if (item is! Map) return '$item';

    final title =
        item['title'] ?? item['name'] ?? tkL10n(context).recommendation;
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
                  SnackBar(content: Text(tkL10n(context).couldNotOpenResource)),
                );
              }
            },
            icon: const Icon(Icons.open_in_new, size: 14),
            label: Text(tkL10n(context).openResource),
            style: TextButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 4),
              visualDensity: VisualDensity.compact,
            ),
          ),
      ],
    );
  }
}
