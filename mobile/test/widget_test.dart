// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage_platform_interface/flutter_secure_storage_platform_interface.dart';
import 'package:flutter_secure_storage/test/test_flutter_secure_storage_platform.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:tuklas/native_app_shell.dart';

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    FlutterSecureStoragePlatform.instance = TestFlutterSecureStoragePlatform({});
  });

  testWidgets('native landing reflects Tuklas branding and system flow', (
    WidgetTester tester,
  ) async {
    tester.view.devicePixelRatio = 1;
    tester.view.physicalSize = const Size(390, 844);
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);

    await tester.pumpWidget(const TuklasApp());
    await tester.pumpAndSettle();

    expect(find.text('tuklas'), findsOneWidget);
    expect(find.text('Powered by Google Gemini AI'), findsOneWidget);
    expect(find.text('AI-Powered Career Path and Skills Development for Youth in Pangasinan'), findsOneWidget);
    expect(find.text('Guidance built around you'), findsOneWidget);
    expect(find.text('Get started'), findsOneWidget);
    expect(find.text('TESDA'), findsOneWidget);
    expect(find.text('Agriculture'), findsNothing);
    expect(find.text('Tuklas website'), findsNothing);
    expect(tester.takeException(), isNull);

    await tester.tap(find.text('Get started'));
    await tester.pumpAndSettle();
    expect(find.text('Create your account'), findsOneWidget);
    expect(find.text('Full name'), findsOneWidget);
  });

  testWidgets('native login exposes reset and social authentication options', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const TuklasApp());
    await tester.pumpAndSettle();

    await tester.tap(find.text('Log in'));
    await tester.pumpAndSettle();

    expect(find.text('Welcome back'), findsOneWidget);
    expect(find.text('Forgot password?'), findsOneWidget);
    expect(find.text('Continue with Google'), findsOneWidget);
    expect(find.text('Continue with Facebook'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });

  testWidgets('landing offers the actual native product pathway', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const TuklasApp());
    await tester.pumpAndSettle();

    await tester.scrollUntilVisible(find.text('Your Tuklas pathway'), 240);
    await tester.pumpAndSettle();

    expect(find.text('Tell us about you'), findsOneWidget);
    expect(find.text('Take the skills assessment'), findsOneWidget);
    expect(find.text('Explore careers and trainings'), findsOneWidget);
    expect(find.text('Coming soon'), findsNWidgets(2));
    expect(tester.takeException(), isNull);
  });
}
