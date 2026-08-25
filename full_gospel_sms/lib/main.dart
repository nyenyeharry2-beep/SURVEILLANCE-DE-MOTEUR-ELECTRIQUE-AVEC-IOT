import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'screens/splash_screen.dart';
import 'theme/app_theme.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);
  runApp(const FullGospelSmsApp());
}

class FullGospelSmsApp extends StatelessWidget {
  const FullGospelSmsApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'FULL GOSPEL SMS',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.theme,
      home: const SplashScreen(),
    );
  }
}
