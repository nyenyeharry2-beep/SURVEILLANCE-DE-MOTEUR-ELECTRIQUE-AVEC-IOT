import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/api_config.dart';
import '../models/sms_models.dart';

class SmsApiException implements Exception {
  final String message;
  SmsApiException(this.message);

  @override
  String toString() => message;
}

class SmsApiService {
  static const _timeout = Duration(seconds: 30);

  Map<String, dynamic> _parseBody(String body) {
    final decoded = jsonDecode(body);
    if (decoded is! Map<String, dynamic>) {
      throw SmsApiException('Réponse API invalide');
    }
    return decoded;
  }

  void _ensureSuccess(Map<String, dynamic> json) {
    final code = json['ErrorCode'] ?? -1;
    if (code != 0) {
      throw SmsApiException(
        json['ErrorDescription']?.toString() ?? 'Erreur API ($code)',
      );
    }
  }

  Uri _uri(String path, [Map<String, String>? query]) {
    final params = {
      'ApiKey': ApiConfig.apiKey,
      'ClientId': ApiConfig.clientId,
      ...?query,
    };
    return Uri.parse('${ApiConfig.baseUrl}$path').replace(queryParameters: params);
  }

  Future<List<BalanceInfo>> getBalance() async {
    final response = await http.get(_uri('/Balance')).timeout(_timeout);
    final json = _parseBody(response.body);
    _ensureSuccess(json);

    final data = json['Data'];
    if (data is! List) return [];

    return data
        .whereType<Map<String, dynamic>>()
        .map(BalanceInfo.fromJson)
        .toList();
  }

  Future<List<SentMessageResult>> sendSms({
    required String message,
    required List<String> mobileNumbers,
  }) async {
    if (message.trim().isEmpty) {
      throw SmsApiException('Le message ne peut pas être vide');
    }
    if (mobileNumbers.isEmpty) {
      throw SmsApiException('Ajoutez au moins un numéro de téléphone');
    }

    final response = await http
        .post(
          Uri.parse('${ApiConfig.baseUrl}/SendSMS'),
          headers: {'Content-Type': 'application/json'},
          body: jsonEncode({
            'ApiKey': ApiConfig.apiKey,
            'ClientId': ApiConfig.clientId,
            'SenderId': ApiConfig.senderId,
            'Message': message.trim(),
            'MobileNumbers': mobileNumbers.join(','),
            'Is_Unicode': false,
            'Is_Flash': false,
            'DataCoding': '0',
            'SchedTime': '',
            'GroupId': '',
          }),
        )
        .timeout(_timeout);

    final json = _parseBody(response.body);
    _ensureSuccess(json);

    final data = json['Data'];
    if (data is! List) return [];

    return data
        .whereType<Map<String, dynamic>>()
        .map(SentMessageResult.fromJson)
        .toList();
  }

  Future<List<SmsHistoryItem>> getHistory({
    int start = 0,
    int length = 50,
    String? fromDate,
    String? toDate,
  }) async {
    final now = DateTime.now();
    final from = fromDate ??
        '01/01/${now.year - 1}';
    final to = toDate ??
        '12/31/${now.year}';

    final response = await http
        .get(_uri('/GetSMS', {
          'start': start.toString(),
          'length': length.toString(),
          'fromdate': from,
          'enddate': to,
        }))
        .timeout(_timeout);

    if (response.statusCode != 200) {
      throw SmsApiException(
        'Impossible de charger l\'historique (HTTP ${response.statusCode})',
      );
    }

    final json = _parseBody(response.body);
    _ensureSuccess(json);

    final data = json['Data'];
    if (data is! List) return [];

    return data
        .whereType<Map<String, dynamic>>()
        .map(SmsHistoryItem.fromJson)
        .toList();
  }
}
