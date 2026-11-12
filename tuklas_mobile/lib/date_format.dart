const _months = [
  'Jan',
  'Feb',
  'Mar',
  'Apr',
  'May',
  'Jun',
  'Jul',
  'Aug',
  'Sep',
  'Oct',
  'Nov',
  'Dec',
];

/// Formats API date values without exposing raw ISO strings or epoch numbers.
String formatTuklasDate(Object? value, {bool includeTime = false}) {
  final parsed = _parseDate(value);
  if (parsed == null) return 'Date unavailable';

  final date = parsed.isUtc ? parsed.toLocal() : parsed;
  final label = '${_months[date.month - 1]} ${date.day}, ${date.year}';
  if (!includeTime) return label;

  final hour = date.hour % 12 == 0 ? 12 : date.hour % 12;
  final minute = date.minute.toString().padLeft(2, '0');
  final period = date.hour < 12 ? 'AM' : 'PM';

  return '$label · $hour:$minute $period';
}

DateTime? _parseDate(Object? value) {
  if (value is DateTime) return value;

  final text = value?.toString().trim();
  if (text == null || text.isEmpty) return null;

  final numericValue = num.tryParse(text);
  if (numericValue != null && numericValue.abs() >= 1000000000) {
    final timestamp = numericValue.abs() < 100000000000
        ? (numericValue * 1000).round()
        : numericValue.round();

    try {
      return DateTime.fromMillisecondsSinceEpoch(timestamp, isUtc: true);
    } on RangeError {
      return null;
    }
  }

  return DateTime.tryParse(text);
}

String formatDateForApi(DateTime value) =>
    '${value.year.toString().padLeft(4, '0')}-'
    '${value.month.toString().padLeft(2, '0')}-'
    '${value.day.toString().padLeft(2, '0')}';
