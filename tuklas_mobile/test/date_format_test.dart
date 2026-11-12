import 'package:flutter_test/flutter_test.dart';
import 'package:tuklas_mobile/date_format.dart';

void main() {
  test('formats a date without exposing ISO numeric parts', () {
    expect(formatTuklasDate('2026-11-12'), 'Nov 12, 2026');
  });

  test('formats date and time with a readable twelve-hour clock', () {
    expect(
      formatTuklasDate('2026-11-12T09:05:00', includeTime: true),
      'Nov 12, 2026 · 9:05 AM',
    );
  });

  test('accepts epoch seconds and milliseconds', () {
    final localDate = DateTime(2026, 11, 12, 9, 5);

    expect(formatTuklasDate(localDate.millisecondsSinceEpoch), 'Nov 12, 2026');
    expect(
      formatTuklasDate(localDate.millisecondsSinceEpoch ~/ 1000),
      'Nov 12, 2026',
    );
  });

  test('shows a clear fallback for missing or invalid dates', () {
    expect(formatTuklasDate(null), 'Date unavailable');
    expect(formatTuklasDate('not-a-date'), 'Date unavailable');
  });
}
