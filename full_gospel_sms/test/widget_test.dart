import 'package:flutter_test/flutter_test.dart';
import 'package:full_gospel_sms/main.dart';

void main() {
  testWidgets('App loads splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const FullGospelSmsApp());
    expect(find.text('FULL GOSPEL'), findsOneWidget);
  });
}
