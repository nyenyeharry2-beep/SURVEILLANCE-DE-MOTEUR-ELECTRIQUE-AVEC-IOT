import 'package:flutter_test/flutter_test.dart';
import 'package:full_gospel_sms/models/sms_models.dart';

void main() {
  group('SmsHistoryItem', () {
    test('fromJson maps GetSMS API fields', () {
      final item = SmsHistoryItem.fromJson({
        'MobileNumber': '243995729003',
        'SenderId': 'FULL GOSPEL',
        'Message': 'bonjour',
        'SubmitDate': '31 Aug 2026 22:09:44',
        'DoneDate': '31 Aug 2026 21:09:00',
        'MessageId': '4a4bc524-3c70-4fc4-bce4-8b8ed28c0b62',
        'Status': 'DELIVRD',
        'ErrorCode': '000',
      });

      expect(item.mobileNumber, '243995729003');
      expect(item.senderId, 'FULL GOSPEL');
      expect(item.message, 'bonjour');
      expect(item.sentDate, '31 Aug 2026 22:09:44');
      expect(item.status, 'DELIVRD');
      expect(item.statusLabel, 'Livré');
    });

    test('statusLabel translates known delivery statuses', () {
      expect(
        SmsHistoryItem(
          mobileNumber: '',
          senderId: '',
          message: '',
          messageId: '',
          status: 'SUBMITTED',
        ).statusLabel,
        'Envoyé',
      );
    });
  });
}
