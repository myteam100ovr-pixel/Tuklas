import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tuklas_mobile/config.dart';
import 'package:tuklas_mobile/screens/workspace_form.dart';
import 'package:tuklas_mobile/screens/login_screen.dart';
import 'package:tuklas_mobile/ui.dart';
import 'package:tuklas_mobile/api.dart';
import 'package:tuklas_mobile/screens/home_shell.dart';

void main() {
  testWidgets('unverified accounts see verification actions before workspace', (
    tester,
  ) async {
    ApiClient.instance.user = {
      'email_verified': false,
      'email': 'youth@example.com',
    };
    addTearDown(() => ApiClient.instance.user = null);
    await tester.pumpWidget(const MaterialApp(home: HomeShell()));
    expect(find.text('Verify your email'), findsOneWidget);
    expect(find.text('Resend verification email'), findsOneWidget);
    expect(find.text('I have verified my email'), findsOneWidget);
    expect(find.text('Log out'), findsOneWidget);
    expect(find.text('TuklasBot'), findsNothing);
  });

  testWidgets('registration consent starts unchecked and blocks submission', (
    tester,
  ) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));
    await tester.tap(find.text('New to Tuklas? Create an account'));
    await tester.pumpAndSettle();
    expect(
      tester.widget<CheckboxListTile>(find.byType(CheckboxListTile)).value,
      isFalse,
    );
    await tester.ensureVisible(find.text('Create account'));
    await tester.tap(find.text('Create account'));
    await tester.pump();
    expect(
      find.text('Please accept the Terms of use and Privacy notice.'),
      findsOneWidget,
    );
  });

  test('mobile API defaults to the Android emulator host', () {
    expect(AppConfig.apiBase, 'http://10.0.2.2:8000/api/mobile');
  });

  testWidgets('sign-in offers email and password without social providers', (
    tester,
  ) async {
    await tester.pumpWidget(const MaterialApp(home: LoginScreen()));
    expect(find.text('Email'), findsOneWidget);
    expect(find.text('Password'), findsOneWidget);
    expect(find.text('Continue with Google'), findsNothing);
    expect(find.text('Continue with Facebook'), findsNothing);
  });

  testWidgets('workspace forms reject empty required values before saving', (
    tester,
  ) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: WorkspaceForm(
          title: 'Add program',
          path: '/trainer/programs',
          fields: [
            WorkspaceField('title', 'Course title', required: true),
            WorkspaceField(
              'status',
              'Status',
              required: true,
              options: ['draft', 'published', 'archived'],
            ),
          ],
        ),
      ),
    );
    await tester.tap(find.text('Save'));
    await tester.pump();
    expect(find.text('Required'), findsNWidgets(2));
    expect(find.text('Saving…'), findsNothing);
  });

  testWidgets(
    'security forms mask passwords on narrow screens with a keyboard',
    (tester) async {
      tester.view.physicalSize = const Size(360, 640);
      tester.view.devicePixelRatio = 1;
      tester.view.viewInsets = const FakeViewPadding(bottom: 280);
      addTearDown(tester.view.reset);
      await tester.pumpWidget(
        const MaterialApp(
          home: WorkspaceForm(
            title: 'Change password',
            path: '/password',
            fields: [
              WorkspaceField(
                'current_password',
                'Current password',
                secret: true,
                required: true,
              ),
              WorkspaceField(
                'password',
                'New password',
                secret: true,
                required: true,
              ),
              WorkspaceField(
                'password_confirmation',
                'Confirm password',
                secret: true,
                required: true,
              ),
            ],
          ),
        ),
      );
      expect(
        tester
            .widgetList<TextField>(find.byType(TextField))
            .every((f) => f.obscureText),
        isTrue,
      );
      await tester.ensureVisible(find.text('Save'));
      await tester.tap(find.text('Save'));
      await tester.pump();
      expect(tester.takeException(), isNull);
    },
  );

  test(
    'documented skill coverage preserves real counts and missing skills',
    () {
      expect(
        tkSkillCoverageDetails({
          'skillCoverage': {'percent': 50, 'matched': 2, 'total': 4},
          'evidencedSkills': ['Writing', 'Teamwork'],
          'skillsToDevelop': ['Design'],
        }),
        '50% documented skill coverage (2 of 4 listed skills)\nDocumented: Writing, Teamwork\nTo develop: Design',
      );
      expect(tkSkillCoverageDetails({}), '');
    },
  );
}
