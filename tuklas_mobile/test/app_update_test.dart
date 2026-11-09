import 'package:flutter_test/flutter_test.dart';
import 'package:tuklas_mobile/app_update.dart';

void main() {
  final manifest = Uri.parse('https://tuklas.example/app-updates/latest.json');

  test('accepts a same-host HTTPS APK update', () {
    final update = AppUpdate.fromJson({
      'version_code': 1001,
      'version_name': '1.0.1',
      'apk_url': 'https://tuklas.example/app-updates/tuklas-mobile-1001.apk',
    }, manifest);

    expect(update?.versionCode, 1001);
    expect(update?.apkUrl.scheme, 'https');
  });

  test('rejects an insecure or unrelated APK URL', () {
    for (final url in [
      'http://tuklas.example/update.apk',
      'https://another.example/update.apk',
    ]) {
      expect(
        AppUpdate.fromJson({
          'version_code': 1001,
          'version_name': '1.0.1',
          'apk_url': url,
        }, manifest),
        isNull,
      );
    }
  });
}
