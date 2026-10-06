// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:tuklas_mobile/main.dart';
import 'package:tuklas_mobile/screens/login_screen.dart';
import 'package:tuklas_mobile/screens/profile_view.dart';
import 'package:tuklas_mobile/theme.dart';
import 'package:tuklas_mobile/ui.dart';

void main() {
  testWidgets('sign-in screen displays the login form', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));

    expect(find.text('tuklas'), findsOneWidget);
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Log in'), findsOneWidget);
  });

  testWidgets('sign-in screen can switch to the mobile registration form', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));

    await tester.tap(find.text('New to Tuklas? Create an account'));
    await tester.pumpAndSettle();

    expect(find.text('Full name'), findsOneWidget);
    expect(find.text('Confirm password'), findsOneWidget);
    expect(find.text('Create account'), findsOneWidget);
    expect(find.text('Already have an account? Log in'), findsOneWidget);
  });

  testWidgets('startup shows Tuklas before opening the login page', (
    WidgetTester tester,
  ) async {
    final startupCheck = Completer<bool>();
    await tester.pumpWidget(
      MaterialApp(home: Gate(checkSignedIn: () => startupCheck.future)),
    );

    expect(find.text('tuklas'), findsOneWidget);
    expect(find.text('Getting things ready'), findsNothing);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);

    startupCheck.complete(false);
    await tester.pump();
    await tester.pumpAndSettle();

    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Log in'), findsOneWidget);
  });

  testWidgets(
    'startup keeps the saved session when the server is unavailable',
    (WidgetTester tester) async {
      final startupCheck = Completer<bool>();
      await tester.pumpWidget(
        MaterialApp(home: Gate(checkSignedIn: () => startupCheck.future)),
      );

      await tester.pump(const Duration(seconds: 16));
      await tester.pumpAndSettle();

      expect(find.text('Can’t reach the Tuklas server'), findsOneWidget);
      expect(find.text('Retry connection'), findsOneWidget);
      expect(find.text('Log in'), findsNothing);
    },
  );

  testWidgets('startup retries and restores the existing signed-in session', (
    WidgetTester tester,
  ) async {
    final firstCheck = Completer<bool>();
    var checks = 0;
    await tester.pumpWidget(
      MaterialApp(
        home: Gate(
          checkSignedIn: () async {
            checks++;
            return checks == 1 ? firstCheck.future : true;
          },
        ),
      ),
    );
    await tester.pump(const Duration(seconds: 16));

    expect(find.text('Can’t reach the Tuklas server'), findsOneWidget);
    await tester.tap(find.text('Retry connection'));
    await tester.pump();

    expect(checks, 2);
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets(
    'bottom navigation hides labels and uses Tuklas purple selection',
    (WidgetTester tester) async {
      int? selectedIndex;
      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            bottomNavigationBar: TkBottomNavigation(
              selectedIndex: 0,
              onDestinationSelected: (index) => selectedIndex = index,
            ),
          ),
        ),
      );

      final navigation = tester.widget<NavigationBar>(
        find.byType(NavigationBar),
      );
      expect(
        navigation.labelBehavior,
        NavigationDestinationLabelBehavior.alwaysHide,
      );
      expect(navigation.indicatorColor, TkColors.violet);
      expect(navigation.height, 64);
      expect(
        IconTheme.of(tester.element(find.byIcon(Icons.dashboard))).color,
        Colors.white,
      );
      for (final label in ['Overview', 'PESO', 'Scanner', 'TESDA', 'Profile']) {
        expect(find.text(label), findsNothing);
      }
      expect(find.byType(TkAvatar), findsOneWidget);
      expect(find.byIcon(Icons.work_outline), findsOneWidget);

      await tester.tap(find.byIcon(Icons.school_outlined));
      expect(selectedIndex, 3);
    },
  );

  testWidgets('top and bottom navigation use matching bordered panels', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: const TkHeader(),
          bottomNavigationBar: TkBottomNavigation(
            selectedIndex: 0,
            onDestinationSelected: (_) {},
          ),
        ),
      ),
    );

    final topPanel = tester.widget<Container>(
      find.byKey(const ValueKey('top-navigation-panel')),
    );
    final bottomPanel = tester.widget<Container>(
      find.byKey(const ValueKey('bottom-navigation-panel')),
    );
    final topDecoration = topPanel.decoration! as BoxDecoration;
    final bottomDecoration = bottomPanel.decoration! as BoxDecoration;

    expect(topDecoration.borderRadius, bottomDecoration.borderRadius);
    expect(topDecoration.border, bottomDecoration.border);
    expect(topDecoration.color, bottomDecoration.color);
  });

  testWidgets('bottom navigation exposes PESO separately from overview', (
    WidgetTester tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          bottomNavigationBar: TkBottomNavigation(
            selectedIndex: 0,
            onDestinationSelected: (_) {},
          ),
        ),
      ),
    );

    final destinations = tester
        .widgetList<NavigationDestination>(find.byType(NavigationDestination))
        .toList();
    expect(destinations.length, 5);
    expect(
      destinations.every((destination) => destination.label.isEmpty),
      isTrue,
    );
    expect(destinations.map((destination) => destination.tooltip).toList(), [
      'Overview',
      'PESO',
      'Scanner',
      'TESDA',
      'Profile',
    ]);
    final selection = tester.widget<NavigationBar>(find.byType(NavigationBar));
    expect(selection.indicatorColor, TkColors.violet);
  });

  testWidgets('profile avatar is a bottom navigation destination', (
    WidgetTester tester,
  ) async {
    int? selectedIndex;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          bottomNavigationBar: TkBottomNavigation(
            selectedIndex: 0,
            onDestinationSelected: (index) => selectedIndex = index,
          ),
        ),
      ),
    );

    expect(find.byKey(const ValueKey('profile-destination')), findsOneWidget);
    expect(find.byType(TkAvatar), findsOneWidget);
    await tester.tap(find.byKey(const ValueKey('profile-destination')));
    expect(selectedIndex, 4);
  });

  testWidgets('profile page provides a working Log out button', (
    WidgetTester tester,
  ) async {
    var loggedOut = false;
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: ProfileView(
            onSaved: () {},
            onLogout: () => loggedOut = true,
            loadProfile: () async => {
              'account': {
                'name': 'Test User',
                'email': 'test@example.com',
                'role_label': 'Youth',
              },
              'youth': null,
              'options': <String, dynamic>{},
            },
          ),
        ),
      ),
    );
    await tester.pumpAndSettle();

    final logoutButton = find.byKey(const ValueKey('profile-logout-button'));
    await tester.scrollUntilVisible(logoutButton, 250);
    expect(logoutButton, findsOneWidget);
    await tester.tap(logoutButton);

    expect(loggedOut, isTrue);
  });
}
