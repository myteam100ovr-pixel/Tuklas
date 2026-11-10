import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError('Firebase has not been configured for web.');
    }

    return switch (defaultTargetPlatform) {
      TargetPlatform.android => android,
      TargetPlatform.iOS => ios,
      _ => throw UnsupportedError(
        'Firebase is configured only for Android and iOS.',
      ),
    };
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyC_pm2lbnT51lPUjlBmcEhfrmQWz-nS1ek',
    appId: '1:834341101694:android:0b4e3f25f691637533382f',
    messagingSenderId: '834341101694',
    projectId: 'tuklas-780a3',
    storageBucket: 'tuklas-780a3.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyDahiFKZD1-78RbJROB2N6VB5t_h8Hbbc8',
    appId: '1:834341101694:ios:d9d912852e12c8b233382f',
    messagingSenderId: '834341101694',
    projectId: 'tuklas-780a3',
    storageBucket: 'tuklas-780a3.firebasestorage.app',
    iosBundleId: 'com.tuklas.ios',
  );
}
