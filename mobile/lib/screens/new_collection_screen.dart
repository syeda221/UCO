import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:uuid/uuid.dart';
import '../core/theme.dart';
import '../models/local_models.dart';
import '../services/local_database.dart';
import '../services/photo_service.dart';
import '../services/sync_engine.dart';
import 'qr_scanner_screen.dart';

class NewCollectionScreen extends StatefulWidget {
  final LocalContainer? preScannedContainer;

  const NewCollectionScreen({super.key, this.preScannedContainer});

  @override
  State<NewCollectionScreen> createState() => _NewCollectionScreenState();
}

class _NewCollectionScreenState extends State<NewCollectionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _volumeCtrl = TextEditingController();
  final _ffaCtrl = TextEditingController();
  final _miuCtrl = TextEditingController();
  final _paymentAmountCtrl = TextEditingController(text: '0');
  final _paymentRefCtrl = TextEditingController();

  final _photoService = PhotoService();
  final String _clientEventUuid = const Uuid().v4();

  LocalVendor? _selectedVendor;
  LocalContainer? _selectedContainer;
  String _scanMethod = 'qr_scan';
  String? _fallbackReason;

  String _deviceType = 'colorimetric_kit';
  String _paymentMethod = 'cash';

  Position? _gpsPosition;
  String? _gpsError;
  bool _capturingGps = true;

  CapturedPhoto? _pickupPhoto;
  CapturedPhoto? _ffaPhoto;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    if (widget.preScannedContainer != null) {
      _selectedContainer = widget.preScannedContainer;
      _scanMethod = 'qr_scan';
    }
    _captureGps();
  }

  @override
  void dispose() {
    _volumeCtrl.dispose();
    _ffaCtrl.dispose();
    _miuCtrl.dispose();
    _paymentAmountCtrl.dispose();
    _paymentRefCtrl.dispose();
    super.dispose();
  }

  Future<void> _captureGps() async {
    setState(() {
      _capturingGps = true;
      _gpsError = null;
    });

    try {
      final serviceEnabled = await Geolocator.isLocationServiceEnabled();
      if (!serviceEnabled) {
        setState(() {
          _gpsError = 'Turn on device GPS/Location service.';
          _capturingGps = false;
        });
        return;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
        setState(() {
          _gpsError = 'Location permission is required.';
          _capturingGps = false;
        });
        return;
      }

      final pos = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 20),
        ),
      );

      if (pos.isMocked) {
        setState(() {
          _gpsPosition = pos;
          _gpsError = 'Mock GPS detected. Disable fake GPS apps to continue.';
          _capturingGps = false;
        });
        return;
      }

      setState(() {
        _gpsPosition = pos;
        _capturingGps = false;
      });
    } catch (e) {
      setState(() {
        _gpsError = 'Could not lock GPS. Move outside and retry.';
        _capturingGps = false;
      });
    }
  }

  /// Strict vendor picker from synced local SQLite list (no free text allowed).
  Future<void> _pickVendor() async {
    final allVendors = await LocalDatabase.instance.getVendors();
    if (!mounted) return;

    if (allVendors.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No synced vendors found. Connect & refresh master data.')),
      );
      return;
    }

    String search = '';
    final chosen = await showModalBottomSheet<LocalVendor>(
      context: context,
      isScrollControlled: true,
      backgroundColor: UcoColors.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => StatefulBuilder(
        builder: (ctx, setModalState) {
          final filtered = allVendors.where((v) {
            final q = search.trim().toLowerCase();
            if (q.isEmpty) return true;
            return v.name.toLowerCase().contains(q) || v.vendorCode.toLowerCase().contains(q);
          }).toList();

          return SizedBox(
            height: MediaQuery.of(ctx).size.height * 0.72,
            child: Column(
              children: [
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 18, 20, 12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Select Synced Vendor',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        onChanged: (v) => setModalState(() => search = v),
                        decoration: const InputDecoration(
                          hintText: 'Search vendor name or code...',
                          prefixIcon: Icon(Icons.search),
                        ),
                      ),
                    ],
                  ),
                ),
                Expanded(
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    itemCount: filtered.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (_, i) {
                      final v = filtered[i];
                      return ListTile(
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                        tileColor: UcoColors.canvas,
                        title: Text(v.name, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                        subtitle: Text('${v.vendorCode} • ${v.address ?? 'Ghana'}', style: const TextStyle(fontSize: 12)),
                        trailing: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: UcoColors.charcoal,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            v.tier.toUpperCase(),
                            style: const TextStyle(color: UcoColors.lime, fontSize: 10, fontWeight: FontWeight.w700),
                          ),
                        ),
                        onTap: () => Navigator.of(ctx).pop(v),
                      );
                    },
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );

    if (chosen != null) {
      setState(() => _selectedVendor = chosen);
    }
  }

  Future<void> _scanContainer() async {
    final result = await Navigator.of(context).push<QrScanResult>(
      MaterialPageRoute(
        builder: (_) => QrScannerScreen(selectedVendorId: _selectedVendor?.id),
      ),
    );
    if (result != null) {
      setState(() {
        _selectedContainer = result.container;
        _scanMethod = result.scanMethod;
        _fallbackReason = result.fallbackReason;
      });
    }
  }

  Future<void> _capturePhoto(bool isPickup) async {
    final photo = await _photoService.captureLivePhoto(
      clientEventUuid: _clientEventUuid,
      photoType: isPickup ? 'pickup_evidence' : 'ffa_reading',
    );
    if (photo == null) return;
    setState(() {
      if (isPickup) {
        _pickupPhoto = photo;
      } else {
        _ffaPhoto = photo;
      }
    });
  }

  Future<void> _saveCollection() async {
    if (_selectedVendor == null) {
      _showMsg('Please select a vendor from the list.');
      return;
    }
    if (_selectedContainer == null) {
      _showMsg('Please scan a container QR (or use manual fallback).');
      return;
    }
    if (_gpsPosition == null || _gpsPosition!.isMocked) {
      _showMsg(_gpsError ?? 'Waiting for valid GPS lock.');
      return;
    }
    if (_pickupPhoto == null && _ffaPhoto == null) {
      _showMsg('At least 1 live camera photo is required.');
      return;
    }
    if (!_formKey.currentState!.validate()) return;

    setState(() => _saving = true);

    try {
      final nowIso = DateTime.now().toUtc().toIso8601String();
      final photos = <OfflinePhoto>[
        if (_pickupPhoto != null)
          OfflinePhoto(
            clientEventUuid: _clientEventUuid,
            photoType: 'pickup_evidence',
            localFilePath: _pickupPhoto!.localPath,
            sha256Hash: _pickupPhoto!.sha256Hash,
            capturedAt: _pickupPhoto!.capturedAtIso,
          ),
        if (_ffaPhoto != null)
          OfflinePhoto(
            clientEventUuid: _clientEventUuid,
            photoType: 'ffa_reading',
            localFilePath: _ffaPhoto!.localPath,
            sha256Hash: _ffaPhoto!.sha256Hash,
            capturedAt: _ffaPhoto!.capturedAtIso,
          ),
      ];

      final collection = OfflineCollection(
        clientEventUuid: _clientEventUuid,
        vendorId: _selectedVendor!.id,
        vendorName: _selectedVendor!.name,
        vendorTier: _selectedVendor!.tier,
        containerId: _selectedContainer!.id,
        containerQrCode: _selectedContainer!.qrCode,
        containerTier: _selectedContainer!.qualiplastTier,
        scanMethod: _scanMethod,
        fallbackReason: _fallbackReason,
        collectedAt: nowIso,
        gpsLat: _gpsPosition!.latitude,
        gpsLng: _gpsPosition!.longitude,
        gpsAccuracyMeters: _gpsPosition!.accuracy,
        gpsIsMocked: _gpsPosition!.isMocked,
        volumeLiters: double.parse(_volumeCtrl.text.trim()),
        fieldFfaPercent: _ffaCtrl.text.trim().isEmpty ? null : double.parse(_ffaCtrl.text.trim()),
        fieldMiuPercent: _miuCtrl.text.trim().isEmpty ? null : double.parse(_miuCtrl.text.trim()),
        fieldDeviceType: _deviceType,
        paymentAmount: double.tryParse(_paymentAmountCtrl.text.trim()) ?? 0.0,
        paymentMethod: _paymentMethod,
        paymentReference: _paymentRefCtrl.text.trim().isEmpty ? null : _paymentRefCtrl.text.trim(),
        photos: photos,
      );

      await LocalDatabase.instance.insertCollectionWithPhotos(collection, photos);

      // Fire-and-forget background sync if online.
      if (SyncEngine.instance.isOnline) {
        SyncEngine.instance.syncAllPending();
      }

      if (!mounted) return;
      _showMsg('Collection saved to offline queue.');
      Navigator.of(context).pop(true);
    } catch (e) {
      _showMsg('Failed to save: $e');
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  void _showMsg(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(msg)));
  }

  @override
  Widget build(BuildContext context) {
    final containerMismatch = _selectedVendor != null &&
        _selectedContainer != null &&
        _selectedContainer!.assignedVendorId != null &&
        _selectedContainer!.assignedVendorId != _selectedVendor!.id;

    return Scaffold(
      backgroundColor: UcoColors.canvas,
      appBar: AppBar(
        title: const Text('Log Collection', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
        actions: [
          GestureDetector(
            onTap: _captureGps,
            child: Container(
              margin: const EdgeInsets.only(right: 14),
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: UcoColors.charcoal,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    _gpsError != null ? Icons.gps_off : Icons.lock,
                    size: 13,
                    color: _gpsError != null ? UcoColors.amber : UcoColors.lime,
                  ),
                  const SizedBox(width: 5),
                  Text(
                    _capturingGps
                        ? 'Locking GPS...'
                        : (_gpsPosition != null
                            ? '${_gpsPosition!.latitude.toStringAsFixed(4)}, ${_gpsPosition!.longitude.toStringAsFixed(4)}'
                            : 'Tap GPS Retry'),
                    style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(18, 8, 18, 110),
          children: [
            if (_gpsError != null)
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFDAD6),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.warning_amber_rounded, color: UcoColors.danger, size: 18),
                    const SizedBox(width: 8),
                    Expanded(child: Text(_gpsError!, style: const TextStyle(fontSize: 12))),
                    TextButton(onPressed: _captureGps, child: const Text('Retry')),
                  ],
                ),
              ),

            // Section 1: Vendor & Container
            _SectionCard(
              title: '1. Vendor & Container QR',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  InkWell(
                    onTap: _pickVendor,
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: UcoColors.canvas,
                        borderRadius: BorderRadius.circular(18),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.storefront_outlined, color: UcoColors.ink),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedVendor?.name ?? 'Select Vendor (Synced List Only)',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 14,
                                    color: _selectedVendor == null ? UcoColors.mutedInk : UcoColors.ink,
                                  ),
                                ),
                                if (_selectedVendor != null)
                                  Text(
                                    '${_selectedVendor!.vendorCode} • ${_selectedVendor!.tier.toUpperCase()}',
                                    style: const TextStyle(fontSize: 11, color: UcoColors.mutedInk),
                                  ),
                              ],
                            ),
                          ),
                          const Icon(Icons.expand_more),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  InkWell(
                    onTap: _scanContainer,
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: _selectedContainer != null ? UcoColors.charcoal : UcoColors.canvas,
                        borderRadius: BorderRadius.circular(18),
                      ),
                      child: Row(
                        children: [
                          Icon(
                            Icons.qr_code_scanner,
                            color: _selectedContainer != null ? UcoColors.lime : UcoColors.ink,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _selectedContainer != null
                                      ? '${_selectedContainer!.qrCode} • ${_selectedContainer!.qualiplastTier}'
                                      : 'Scan Container QR (or Fallback)',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w700,
                                    fontSize: 14,
                                    color: _selectedContainer != null ? Colors.white : UcoColors.mutedInk,
                                  ),
                                ),
                                if (_scanMethod == 'manual_fallback')
                                  Text(
                                    'Manual Fallback: ${_fallbackReason ?? ''}',
                                    style: const TextStyle(fontSize: 11, color: UcoColors.amber),
                                  ),
                              ],
                            ),
                          ),
                          Text(
                            _selectedContainer != null ? 'Change' : 'Scan',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                              color: _selectedContainer != null ? UcoColors.lime : UcoColors.ink,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  if (containerMismatch) ...[
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Text(
                        'Warning: This container QR is assigned to a different vendor. Saving will flag for admin review.',
                        style: TextStyle(fontSize: 11, color: Color(0xFF92400E), fontWeight: FontWeight.w600),
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Section 2: Volume & Field Quality
            _SectionCard(
              title: '2. Volume & Field Quality',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  TextFormField(
                    controller: _volumeCtrl,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(
                      labelText: 'Volume (Liters) *',
                      suffixText: 'L',
                    ),
                    validator: (v) {
                      final n = double.tryParse(v ?? '');
                      if (n == null || n <= 0) return 'Enter valid volume > 0';
                      return null;
                    },
                  ),
                  const SizedBox(height: 14),
                  const Text('Testing Device *', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    children: [
                      _choiceChip('Colorimetric Kit', 'colorimetric_kit', _deviceType, (v) => setState(() => _deviceType = v)),
                      _choiceChip('Testo', 'testo', _deviceType, (v) => setState(() => _deviceType = v)),
                      _choiceChip('Ebro', 'ebro', _deviceType, (v) => setState(() => _deviceType = v)),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Expanded(
                        child: TextFormField(
                          controller: _ffaCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: const InputDecoration(labelText: 'Field FFA %', suffixText: '%'),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: TextFormField(
                          controller: _miuCtrl,
                          keyboardType: const TextInputType.numberWithOptions(decimal: true),
                          decoration: const InputDecoration(labelText: 'MIU % (Opt.)', suffixText: '%'),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Section 3: Live Camera Only Photos
            _SectionCard(
              title: '3. Live Photo Evidence (Min 1 Required)',
              child: Row(
                children: [
                  Expanded(
                    child: _PhotoCaptureTile(
                      label: 'Pickup Photo',
                      photo: _pickupPhoto,
                      onTap: () => _capturePhoto(true),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _PhotoCaptureTile(
                      label: 'FFA Reading',
                      photo: _ffaPhoto,
                      onTap: () => _capturePhoto(false),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Section 4: Payment
            _SectionCard(
              title: '4. Payment Record',
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  TextFormField(
                    controller: _paymentAmountCtrl,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    decoration: const InputDecoration(labelText: 'Amount Paid (GHS)', prefixText: 'GHS '),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    children: [
                      _choiceChip('Cash', 'cash', _paymentMethod, (v) => setState(() => _paymentMethod = v)),
                      _choiceChip('MoMo', 'momo', _paymentMethod, (v) => setState(() => _paymentMethod = v)),
                      _choiceChip('Credit', 'credit', _paymentMethod, (v) => setState(() => _paymentMethod = v)),
                      _choiceChip('Bank', 'bank', _paymentMethod, (v) => setState(() => _paymentMethod = v)),
                    ],
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _paymentRefCtrl,
                    decoration: const InputDecoration(labelText: 'Payment Reference (Optional)'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 8, 18, 14),
          child: InkWell(
            onTap: _saving ? null : _saveCollection,
            borderRadius: BorderRadius.circular(999),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
              decoration: BoxDecoration(
                color: UcoColors.charcoal,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    _saving ? 'Saving Offline...' : 'Save Collection (Offline Ready)',
                    style: const TextStyle(color: Colors.white, fontSize: 15, fontWeight: FontWeight.w700),
                  ),
                  Container(
                    width: 36,
                    height: 36,
                    decoration: const BoxDecoration(color: UcoColors.lime, shape: BoxShape.circle),
                    child: const Icon(Icons.check, color: UcoColors.ink),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _choiceChip(String label, String value, String current, ValueChanged<String> onSelect) {
    final active = value == current;
    return ChoiceChip(
      label: Text(label),
      selected: active,
      selectedColor: UcoColors.charcoal,
      backgroundColor: UcoColors.canvas,
      labelStyle: TextStyle(
        color: active ? UcoColors.lime : UcoColors.ink,
        fontWeight: FontWeight.w600,
        fontSize: 12,
      ),
      shape: const StadiumBorder(),
      side: BorderSide.none,
      onSelected: (_) => onSelect(value),
    );
  }
}

class _SectionCard extends StatelessWidget {
  final String title;
  final Widget child;

  const _SectionCard({required this.title, required this.child});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: UcoColors.card,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: UcoColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
          const SizedBox(height: 12),
          child,
        ],
      ),
    );
  }
}

class _PhotoCaptureTile extends StatelessWidget {
  final String label;
  final CapturedPhoto? photo;
  final VoidCallback onTap;

  const _PhotoCaptureTile({required this.label, required this.photo, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        height: 124,
        decoration: BoxDecoration(
          color: UcoColors.canvas,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: photo != null ? UcoColors.lime : UcoColors.border, width: 1.5),
        ),
        child: photo != null
            ? ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    Image.file(File(photo!.localPath), fit: BoxFit.cover),
                    Positioned(
                      bottom: 6,
                      left: 6,
                      right: 6,
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: UcoColors.charcoal.withValues(alpha: 0.85),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          '$label • Retake',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: UcoColors.lime, fontSize: 10, fontWeight: FontWeight.w700),
                        ),
                      ),
                    ),
                  ],
                ),
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const CircleAvatar(
                    radius: 20,
                    backgroundColor: UcoColors.charcoal,
                    child: Icon(Icons.photo_camera, color: UcoColors.lime, size: 20),
                  ),
                  const SizedBox(height: 8),
                  Text(label, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                  const Text('Live Camera', style: TextStyle(fontSize: 10, color: UcoColors.mutedInk)),
                ],
              ),
      ),
    );
  }
}
