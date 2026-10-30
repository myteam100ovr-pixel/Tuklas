import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:tuklas_mobile/screens/workspace_form.dart';
import 'package:tuklas_mobile/screens/social_callback_screen.dart';
import 'package:tuklas_mobile/ui.dart';

void main() {
  testWidgets('social sign-in cancellation returns a usable sign-in action', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        home: SocialCallbackScreen(
          uri: Uri.parse('tuklas://auth/social?error=Sign-in%20cancelled'),
        ),
      ),
    );
    expect(find.text('Sign-in cancelled'), findsOneWidget);
    expect(find.text('Back to sign-in'), findsOneWidget);
    expect(find.text('Continue'), findsNothing);
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
