import 'package:flutter_test/flutter_test.dart';
import 'package:full_gospel_sms/main.dart';

void main() {
  testWidgets('App loads splash screen with logo label', (WidgetTester tester) async {
    await tester.pumpWidget(const FullGospelSmsApp());
    expect(find.text('FULL GOSPEL SMS'), findsOneWidget);
    expect(find.text('Envoi de messages'), findsOneWidget);
  });
}
