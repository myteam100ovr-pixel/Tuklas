import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import 'config.dart';

class AppUpdate {
  const AppUpdate({
    required this.versionCode,
    required this.versionName,
    required this.apkUrl,
    required this.patchNotes,
  });

  final int versionCode;
  final String versionName;
  final Uri apkUrl;
  final String patchNotes;

  static AppUpdate? fromJson(Object? value, Uri manifestUrl) {
    if (value is! Map<String, dynamic>) {
      return null;
    }
    final versionCode = value['version_code'];
    final versionName = value['version_name'];
    final apk = value['apk_url'];
    if (versionCode is! int ||
        versionCode < 1 ||
        versionName is! String ||
        versionName.isEmpty ||
        apk is! String) {
      return null;
    }
    final apkUrl = Uri.tryParse(apk);
    if (apkUrl == null ||
        apkUrl.scheme != 'https' ||
        apkUrl.host != manifestUrl.host ||
        apkUrl.path.isEmpty) {
      return null;
    }
    return AppUpdate(
      versionCode: versionCode,
      versionName: versionName,
      apkUrl: apkUrl,
      patchNotes: value['patch_notes'] is String
          ? value['patch_notes'] as String
          : '',
    );
  }
}

class AppUpdateWatcher extends StatefulWidget {
  const AppUpdateWatcher({required this.child, super.key});

  final Widget child;

  @override
  State<AppUpdateWatcher> createState() => _AppUpdateWatcherState();
}

class _AppUpdateWatcherState extends State<AppUpdateWatcher>
    with WidgetsBindingObserver {
  static const _platform = MethodChannel('com.tuklas_mobile/app_version');
  int? _lastOfferedVersion;
  bool _checking = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) => unawaited(_check()));
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      unawaited(_check());
    }
  }

  Future<void> _check() async {
    if (!mounted || _checking) {
      return;
    }
    final api = Uri.tryParse(AppConfig.apiBase);
    if (api == null || api.scheme != 'https') {
      return;
    }
    final manifestUrl = api.replace(
      path: '/app-updates/latest.json',
      query: null,
      fragment: null,
    );
    _checking = true;
    try {
      final installedVersion = await _platform.invokeMethod<int>('versionCode');
      if (installedVersion == null) {
        return;
      }
      final response = await http
          .get(manifestUrl)
          .timeout(const Duration(seconds: 8));
      if (response.statusCode != 200 || !mounted) {
        return;
      }
      final update = AppUpdate.fromJson(jsonDecode(response.body), manifestUrl);
      if (update == null || !mounted) {
        return;
      }
      final preferences = await SharedPreferences.getInstance();
      final notesShownFor =
          preferences.getInt('patch_notes_shown_version') ?? 0;
      if (update.versionCode == installedVersion &&
          update.versionCode > notesShownFor &&
          update.patchNotes.trim().isNotEmpty) {
        await preferences.setInt(
          'patch_notes_shown_version',
          update.versionCode,
        );
        if (!mounted) return;
        await showDialog<void>(
          context: context,
          builder: (dialogContext) => AlertDialog(
            title: Text('What’s new in ${update.versionName}'),
            content: SingleChildScrollView(child: Text(update.patchNotes)),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(dialogContext),
                child: const Text('Done'),
              ),
            ],
          ),
        );
        return;
      }
      if (update.versionCode <= installedVersion ||
          update.versionCode == _lastOfferedVersion) {
        return;
      }
      if (!mounted) {
        return;
      }
      _lastOfferedVersion = update.versionCode;
      await showDialog<void>(
        context: context,
        builder: (dialogContext) => AlertDialog(
          title: const Text('Tuklas update available'),
          content: Text(
            'Version ${update.versionName} is ready.\n\n${update.patchNotes}\n\nDownload the APK and confirm the Android installation to update.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(dialogContext),
              child: const Text('Later'),
            ),
            FilledButton(
              onPressed: () async {
                Navigator.pop(dialogContext);
                await launchUrl(
                  update.apkUrl,
                  mode: LaunchMode.externalApplication,
                );
              },
              child: const Text('Download update'),
            ),
          ],
        ),
      );
    } catch (_) {
      // Updates are optional when the server or platform is unavailable.
    } finally {
      _checking = false;
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
