import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tuklas_mobile/screens/welcome_screen.dart';
import 'package:tuklas_mobile/theme.dart';

void main() {
  testWidgets('first launch explains the app and remembers completion', (
    tester,
  ) async {
    SharedPreferences.setMockInitialValues({});
    tester.view.physicalSize = const Size(320, 640);
    tester.view.devicePixelRatio = 1;
    addTearDown(tester.view.resetPhysicalSize);
    addTearDown(tester.view.resetDevicePixelRatio);
    await tester.pumpWidget(
      MaterialApp(
        theme: tkTheme(Brightness.light),
        home: const FirstLaunch(child: Scaffold(body: Text('Sign in next'))),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('What to expect'), findsOneWidget);
    expect(find.text('Sign in next'), findsNothing);
    await tester.scrollUntilVisible(find.text('Get started'), 200);
    await tester.pumpAndSettle();
    await tester.ensureVisible(find.byType(FilledButton));
    await tester.pumpAndSettle();
    expect(tester.takeException(), isNull);
    await tester.tap(find.text('Get started'));
    await tester.pumpAndSettle();
    expect(find.text('Sign in next'), findsOneWidget);
    final preferences = await SharedPreferences.getInstance();
    expect(preferences.getBool(FirstLaunch.preferenceKey), isTrue);
  });

  testWidgets('returning installation skips the welcome screen', (
    tester,
  ) async {
    SharedPreferences.setMockInitialValues({FirstLaunch.preferenceKey: true});
    await tester.pumpWidget(
      const MaterialApp(
        home: FirstLaunch(child: Scaffold(body: Text('Your app'))),
      ),
    );
    await tester.pumpAndSettle();
    expect(find.text('Your app'), findsOneWidget);
    expect(find.text('What to expect'), findsNothing);
  });
}
