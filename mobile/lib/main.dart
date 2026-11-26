import 'package:flutter/material.dart';
import 'core/config.dart';
import 'core/theme.dart';
import 'screens/home_screen.dart';
import 'screens/login_screen.dart';
import 'services/sync_engine.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await SyncEngine.instance.registerBackgroundWorker();
  final token = await AppConfig.getToken();

  runApp(UcoCollectorApp(isLoggedIn: token != null && token.isNotEmpty));
}

class UcoCollectorApp extends StatelessWidget {
  final bool isLoggedIn;

  const UcoCollectorApp({super.key, required this.isLoggedIn});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'UCO Trace Ghana',
      debugShowCheckedModeBanner: false,
      theme: UcoTheme.light(),
      home: isLoggedIn ? const HomeScreen() : const LoginScreen(),
    );
  }
}
