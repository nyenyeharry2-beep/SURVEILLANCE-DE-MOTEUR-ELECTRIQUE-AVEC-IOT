class ApiResponse<T> {
  final int errorCode;
  final String errorDescription;
  final T? data;

  ApiResponse({
    required this.errorCode,
    required this.errorDescription,
    this.data,
  });

  bool get isSuccess => errorCode == 0;
}

class BalanceInfo {
  final String pluginType;
  final String credits;

  BalanceInfo({required this.pluginType, required this.credits});

  factory BalanceInfo.fromJson(Map<String, dynamic> json) {
    return BalanceInfo(
      pluginType: json['PluginType']?.toString() ?? 'SMS',
      credits: json['Credits']?.toString() ?? '0',
    );
  }
}

class SentMessageResult {
  final String mobileNumber;
  final String messageId;

  SentMessageResult({required this.mobileNumber, required this.messageId});

  factory SentMessageResult.fromJson(Map<String, dynamic> json) {
    return SentMessageResult(
      mobileNumber: json['MobileNumber']?.toString() ?? '',
      messageId: json['MessageId']?.toString() ?? '',
    );
  }
}

class SmsHistoryItem {
  final String mobileNumber;
  final String senderId;
  final String message;
  final String messageId;
  final String? sentDate;
  final String? status;

  SmsHistoryItem({
    required this.mobileNumber,
    required this.senderId,
    required this.message,
    required this.messageId,
    this.sentDate,
    this.status,
  });

  factory SmsHistoryItem.fromJson(Map<String, dynamic> json) {
    return SmsHistoryItem(
      mobileNumber: json['MobileNumber']?.toString() ?? '',
      senderId: json['SenderId']?.toString() ?? '',
      message: json['Message']?.toString() ?? '',
      messageId: json['MessageId']?.toString() ?? '',
      sentDate: json['SentDate']?.toString() ?? json['Date']?.toString(),
      status: json['Status']?.toString() ?? json['DeliveryStatus']?.toString(),
    );
  }
}
