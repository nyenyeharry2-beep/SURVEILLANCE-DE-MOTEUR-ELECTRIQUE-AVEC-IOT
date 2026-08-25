class PhoneUtils {
  static String normalize(String input) {
    var number = input.trim();
    if (number.isEmpty) return '';

    number = number.replaceAll(RegExp(r'[\s\-\(\)\.]'), '');

    if (number.startsWith('+')) {
      number = number.substring(1);
    } else if (number.startsWith('00')) {
      number = number.substring(2);
    } else if (number.startsWith('0') && number.length >= 9) {
      number = '243${number.substring(1)}';
    }

    return number.replaceAll(RegExp(r'[^0-9]'), '');
  }

  static List<String> parseNumbers(String input) {
    final raw = input.split(RegExp(r'[,;\n\r]+'));
    final numbers = <String>{};

    for (final part in raw) {
      final normalized = normalize(part);
      if (normalized.length >= 9) {
        numbers.add(normalized);
      }
    }

    return numbers.toList();
  }
}
