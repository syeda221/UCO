import 'package:flutter/material.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../core/theme.dart';
import '../models/local_models.dart';
import '../services/local_database.dart';

class QrScanResult {
  final LocalContainer container;
  final String scanMethod; // qr_scan | manual_fallback
  final String? fallbackReason;

  const QrScanResult({
    required this.container,
    required this.scanMethod,
    this.fallbackReason,
  });
}

class QrScannerScreen extends StatefulWidget {
  final int? selectedVendorId;

  const QrScannerScreen({super.key, this.selectedVendorId});

  @override
  State<QrScannerScreen> createState() => _QrScannerScreenState();
}

class _QrScannerScreenState extends State<QrScannerScreen> {
  final MobileScannerController _scannerController = MobileScannerController();
  bool _torchOn = false;
  bool _processing = false;
  String? _statusMessage;

  @override
  void dispose() {
    _scannerController.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    final code = capture.barcodes.firstOrNull?.rawValue;
    if (code == null || code.trim().isEmpty) return;

    setState(() {
      _processing = true;
      _statusMessage = 'Checking $code...';
    });

    final container = await LocalDatabase.instance.findContainerByQr(code);
    if (!mounted) return;

    if (container == null) {
      setState(() {
        _processing = false;
        _statusMessage = 'Unknown QR "$code" — use registered container or fallback.';
      });
      return;
    }

    Navigator.of(context).pop(
      QrScanResult(container: container, scanMethod: 'qr_scan'),
    );
  }

  /// Required fallback when QR sticker is oily/damaged in the field.
  Future<void> _openManualFallback() async {
    final containers = await LocalDatabase.instance.getContainers(vendorId: widget.selectedVendorId);
    if (!mounted) return;

    if (containers.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No cached containers available. Refresh master data first.')),
      );
      return;
    }

    LocalContainer selected = containers.first;
    final reasonCtrl = TextEditingController();
    final formKey = GlobalKey<FormState>();

    final outcome = await showModalBottomSheet<QrScanResult>(
      context: context,
      isScrollControlled: true,
      backgroundColor: UcoColors.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) => Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
          ),
          child: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Unreadable QR Fallback',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Select the container manually and enter why the QR could not be scanned (logged for audit).',
                  style: TextStyle(fontSize: 12, color: UcoColors.mutedInk),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<LocalContainer>(
                  initialValue: selected,
                  items: containers
                      .map((c) => DropdownMenuItem(
                            value: c,
                            child: Text('${c.qrCode} • ${c.qualiplastTier}'),
                          ))
                      .toList(),
                  onChanged: (v) {
                    if (v != null) setModalState(() => selected = v);
                  },
                  decoration: const InputDecoration(labelText: 'Select Container'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: reasonCtrl,
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Mandatory Reason *',
                    hintText: 'e.g. QR sticker covered in oil / torn',
                  ),
                  validator: (v) => (v == null || v.trim().length < 5)
                      ? 'Please enter a clear reason (min 5 chars)'
                      : null,
                ),
                const SizedBox(height: 18),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: UcoColors.charcoal,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: const StadiumBorder(),
                  ),
                  onPressed: () {
                    if (!formKey.currentState!.validate()) return;
                    Navigator.of(ctx).pop(
                      QrScanResult(
                        container: selected,
                        scanMethod: 'manual_fallback',
                        fallbackReason: reasonCtrl.text.trim(),
                      ),
                    );
                  },
                  child: const Text('Confirm Manual Fallback', style: TextStyle(fontWeight: FontWeight.w700)),
                ),
              ],
            ),
          ),
        ),
      ),
    );

    if (outcome != null && mounted) {
      Navigator.of(context).pop(outcome);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          MobileScanner(
            controller: _scannerController,
            onDetect: _onDetect,
          ),
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      CircleAvatar(
                        backgroundColor: UcoColors.charcoal.withValues(alpha: 0.85),
                        child: IconButton(
                          icon: const Icon(Icons.close, color: Colors.white),
                          onPressed: () => Navigator.of(context).pop(),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                        decoration: BoxDecoration(
                          color: UcoColors.charcoal.withValues(alpha: 0.85),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: const Text(
                          'Scan Container QR',
                          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13),
                        ),
                      ),
                      const SizedBox(width: 40),
                    ],
                  ),
                  Center(
                    child: Container(
                      width: 240,
                      height: 240,
                      decoration: BoxDecoration(
                        border: Border.all(color: UcoColors.lime, width: 3),
                        borderRadius: BorderRadius.circular(24),
                      ),
                    ),
                  ),
                  Column(
                    children: [
                      if (_statusMessage != null)
                        Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                          decoration: BoxDecoration(
                            color: UcoColors.charcoal,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Text(
                            _statusMessage!,
                            style: const TextStyle(color: Colors.white, fontSize: 12),
                            textAlign: TextAlign.center,
                          ),
                        ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                        decoration: BoxDecoration(
                          color: UcoColors.charcoal,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            TextButton.icon(
                              onPressed: () {
                                _scannerController.toggleTorch();
                                setState(() => _torchOn = !_torchOn);
                              },
                              icon: Icon(
                                _torchOn ? Icons.flashlight_on : Icons.flashlight_off,
                                color: _torchOn ? UcoColors.lime : Colors.white,
                              ),
                              label: Text(
                                _torchOn ? 'Flash On' : 'Flash Off',
                                style: const TextStyle(color: Colors.white, fontSize: 12),
                              ),
                            ),
                            TextButton.icon(
                              onPressed: _openManualFallback,
                              icon: const Icon(Icons.edit_note, color: UcoColors.lime),
                              label: const Text(
                                'QR Unreadable?',
                                style: TextStyle(color: UcoColors.lime, fontWeight: FontWeight.w700, fontSize: 12),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
