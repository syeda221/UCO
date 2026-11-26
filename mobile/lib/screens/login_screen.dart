import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import '../core/config.dart';
import '../core/theme.dart';
import '../services/api_client.dart';
import '../services/sync_engine.dart';
import 'home_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _identifierController = TextEditingController();
  final _passwordController = TextEditingController();
  final _apiClient = ApiClient();

  bool _obscurePassword = true;
  bool _loading = false;
  String? _error;

  @override
  void dispose() {
    _identifierController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _openServerUrlDialog() async {
    final currentUrl = await AppConfig.getBaseUrl();
    final ctrl = TextEditingController(text: currentUrl);
    if (!mounted) return;

    await showDialog<void>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('API Server URL'),
        content: TextField(
          controller: ctrl,
          decoration: const InputDecoration(
            hintText: 'https://api.yourdomain.com/api/v1',
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: const Text('Cancel')),
          FilledButton(
            onPressed: () async {
              await AppConfig.setBaseUrl(ctrl.text);
              if (ctx.mounted) Navigator.pop(ctx);
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final res = await _apiClient.login(
        identifier: _identifierController.text.trim(),
        password: _passwordController.text,
      );

      final collector = Map<String, dynamic>.from(res['collector'] as Map);
      await AppConfig.saveSession(
        token: res['token'] as String,
        collectorId: collector['id'] as int,
        name: collector['name'] as String,
        phone: (collector['phone'] as String?) ?? (collector['email'] as String? ?? ''),
      );

      await SyncEngine.instance.refreshMasterData();
      await SyncEngine.instance.initMonitoring();

      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } on DioException catch (e) {
      final msg = e.response?.data is Map ? e.response?.data['message']?.toString() : null;
      setState(() => _error = msg ?? 'Could not connect to server. Check internet or API URL.');
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: UcoColors.canvas,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    IconButton(
                      onPressed: _openServerUrlDialog,
                      icon: const Icon(Icons.settings_outlined, color: UcoColors.mutedInk),
                      tooltip: 'Configure Server URL',
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Center(
                  child: Container(
                    width: 68,
                    height: 68,
                    decoration: BoxDecoration(
                      color: UcoColors.charcoal,
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: const Center(
                      child: Icon(Icons.water_drop_rounded, color: UcoColors.lime, size: 32),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'UCO Trace Ghana',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 26, fontWeight: FontWeight.w800, color: UcoColors.ink),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Field Collector Portal',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 14, color: UcoColors.mutedInk),
                ),
                const SizedBox(height: 28),
                Container(
                  padding: const EdgeInsets.all(22),
                  decoration: BoxDecoration(
                    color: UcoColors.card,
                    borderRadius: BorderRadius.circular(26),
                    border: Border.all(color: UcoColors.border),
                  ),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                color: UcoColors.canvas,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  CircleAvatar(radius: 4, backgroundColor: UcoColors.lime),
                                  SizedBox(width: 8),
                                  Text(
                                    'Offline-First Ready',
                                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
                                  ),
                                ],
                              ),
                            ),
                            const Text('Phase 1', style: TextStyle(fontSize: 12, color: UcoColors.mutedInk)),
                          ],
                        ),
                        const SizedBox(height: 20),
                        if (_error != null) ...[
                          Container(
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFFDAD6),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Text(
                              _error!,
                              style: const TextStyle(fontSize: 12, color: Color(0xFF93000A)),
                            ),
                          ),
                          const SizedBox(height: 14),
                        ],
                        const Text('Phone or Email', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        TextFormField(
                          controller: _identifierController,
                          keyboardType: TextInputType.emailAddress,
                          decoration: const InputDecoration(
                            hintText: '+233240000001 or email',
                            prefixIcon: Icon(Icons.badge_outlined, color: UcoColors.mutedInk),
                          ),
                          validator: (v) => (v == null || v.trim().isEmpty) ? 'Required' : null,
                        ),
                        const SizedBox(height: 14),
                        const Text('Password', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                        const SizedBox(height: 6),
                        TextFormField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          decoration: InputDecoration(
                            hintText: 'Enter your password',
                            prefixIcon: const Icon(Icons.lock_outline, color: UcoColors.mutedInk),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                color: UcoColors.mutedInk,
                              ),
                              onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                            ),
                          ),
                          validator: (v) => (v == null || v.isEmpty) ? 'Required' : null,
                        ),
                        const SizedBox(height: 22),
                        InkWell(
                          onTap: _loading ? null : _handleLogin,
                          borderRadius: BorderRadius.circular(999),
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                            decoration: BoxDecoration(
                              color: UcoColors.charcoal,
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  _loading ? 'Signing In & Syncing...' : 'Sign In',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 15,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                Container(
                                  width: 38,
                                  height: 38,
                                  decoration: const BoxDecoration(
                                    color: UcoColors.lime,
                                    shape: BoxShape.circle,
                                  ),
                                  child: _loading
                                      ? const Padding(
                                          padding: EdgeInsets.all(10),
                                          child: CircularProgressIndicator(strokeWidth: 2, color: UcoColors.ink),
                                        )
                                      : const Icon(Icons.arrow_forward, color: UcoColors.ink, size: 20),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 18),
                const Text(
                  'Vendor & QR container registry caches locally after login.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 12, color: UcoColors.mutedInk),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
