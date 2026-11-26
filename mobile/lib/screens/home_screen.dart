import 'package:flutter/material.dart';
import '../core/config.dart';
import '../core/theme.dart';
import '../models/local_models.dart';
import '../services/api_client.dart';
import '../services/local_database.dart';
import '../services/sync_engine.dart';
import 'collection_detail_screen.dart';
import 'login_screen.dart';
import 'new_collection_screen.dart';
import 'qr_scanner_screen.dart';
import 'sync_queue_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String _collectorName = 'Collector';
  String _collectorPhone = '';
  String? _lastMasterSync;
  String _search = '';

  int _pendingEvents = 0;
  int _pendingPhotos = 0;
  int _todayCount = 0;
  double _todayLiters = 0;
  List<OfflineCollection> _collections = [];

  @override
  void initState() {
    super.initState();
    SyncEngine.instance.addListener(_onSyncUpdate);
    SyncEngine.instance.initMonitoring();
    _loadData();
  }

  @override
  void dispose() {
    SyncEngine.instance.removeListener(_onSyncUpdate);
    super.dispose();
  }

  void _onSyncUpdate() {
    if (mounted) _loadData();
  }

  Future<void> _loadData() async {
    final name = await AppConfig.getCollectorName();
    final phone = await AppConfig.getCollectorPhone();
    final masterSync = await LocalDatabase.instance.getLastMasterSync();
    final stats = await LocalDatabase.instance.getDashboardStats();
    final collections = await LocalDatabase.instance.getCollections();

    if (!mounted) return;
    setState(() {
      _collectorName = name ?? 'Collector';
      _collectorPhone = phone ?? '';
      _lastMasterSync = masterSync;
      _pendingEvents = stats.pendingEvents;
      _pendingPhotos = stats.pendingPhotos;
      _todayCount = stats.todayCount;
      _todayLiters = stats.todayLiters;
      _collections = collections;
    });
  }

  Future<void> _triggerSync() async {
    await SyncEngine.instance.refreshMasterData();
    final res = await SyncEngine.instance.syncAllPending();
    await _loadData();
    if (!mounted) return;

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          res.authError ?? 'Sync done: ${res.syncedEvents} completed, ${res.failedEvents} pending.',
        ),
      ),
    );
  }

  Future<void> _quickQrToNewCollection() async {
    final scan = await Navigator.of(context).push<QrScanResult>(
      MaterialPageRoute(builder: (_) => const QrScannerScreen()),
    );
    if (scan == null || !mounted) return;
    await Navigator.of(context).push(
      MaterialPageRoute(
        builder: (_) => NewCollectionScreen(preScannedContainer: scan.container),
      ),
    );
    _loadData();
  }

  Future<void> _openProfileSheet() async {
    await showModalBottomSheet<void>(
      context: context,
      backgroundColor: UcoColors.card,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(22),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(_collectorName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
            Text(_collectorPhone, style: const TextStyle(fontSize: 13, color: UcoColors.mutedInk)),
            const SizedBox(height: 16),
            ListTile(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              tileColor: UcoColors.canvas,
              leading: const Icon(Icons.cloud_download_outlined),
              title: const Text('Refresh Vendors & Containers', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
              onTap: () async {
                Navigator.pop(ctx);
                final ok = await SyncEngine.instance.refreshMasterData();
                await _loadData();
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(content: Text(ok ? 'Master data refreshed.' : 'Could not refresh (offline?).')),
                );
              },
            ),
            const SizedBox(height: 10),
            ListTile(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
              tileColor: const Color(0xFFFFDAD6),
              leading: const Icon(Icons.logout, color: UcoColors.danger),
              title: const Text('Sign Out', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14, color: UcoColors.danger)),
              subtitle: _pendingEvents > 0
                  ? Text('Warning: $_pendingEvents pickup(s) still pending upload!', style: const TextStyle(fontSize: 11))
                  : null,
              onTap: () async {
                Navigator.pop(ctx);
                await ApiClient().logout();
                await AppConfig.clearSession();
                if (!mounted) return;
                Navigator.of(context).pushReplacement(
                  MaterialPageRoute(builder: (_) => const LoginScreen()),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final visible = _collections.where((c) {
      if (_search.trim().isEmpty) return true;
      final q = _search.trim().toLowerCase();
      return c.vendorName.toLowerCase().contains(q) || c.containerQrCode.toLowerCase().contains(q);
    }).toList();

    final isOnline = SyncEngine.instance.isOnline;

    return Scaffold(
      backgroundColor: UcoColors.canvas,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _triggerSync,
          child: ListView(
            padding: const EdgeInsets.fromLTRB(18, 12, 18, 110),
            children: [
              // Header
              Row(
                children: [
                  GestureDetector(
                    onTap: _openProfileSheet,
                    child: CircleAvatar(
                      radius: 22,
                      backgroundColor: UcoColors.charcoal,
                      child: Text(
                        _collectorName.isNotEmpty ? _collectorName[0].toUpperCase() : 'C',
                        style: const TextStyle(color: UcoColors.lime, fontWeight: FontWeight.w800),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(_collectorName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                        const Row(
                          children: [
                            Icon(Icons.location_on_outlined, size: 13, color: UcoColors.mutedInk),
                            SizedBox(width: 3),
                            Text('Ghana Field Ops', style: TextStyle(fontSize: 12, color: UcoColors.mutedInk)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  CircleAvatar(
                    backgroundColor: UcoColors.card,
                    child: IconButton(
                      icon: const Icon(Icons.sync_problem_outlined, color: UcoColors.ink),
                      onPressed: () async {
                        await Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => const SyncQueueScreen()),
                        );
                        _loadData();
                      },
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Search & Quick QR Scan
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      onChanged: (v) => setState(() => _search = v),
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: UcoColors.card,
                        hintText: 'Search synced pickups or QR...',
                        prefixIcon: const Icon(Icons.search, color: UcoColors.mutedInk),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(999),
                          borderSide: BorderSide.none,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  InkWell(
                    onTap: _quickQrToNewCollection,
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      width: 52,
                      height: 52,
                      decoration: BoxDecoration(
                        color: UcoColors.card,
                        borderRadius: BorderRadius.circular(18),
                        border: Border.all(color: UcoColors.border),
                      ),
                      child: const Icon(Icons.qr_code_scanner, color: UcoColors.ink),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              if (SyncEngine.instance.authBlockedMessage != null)
                Container(
                  margin: const EdgeInsets.only(bottom: 14),
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFFDAD6),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: Text(
                    'Sync Paused: ${SyncEngine.instance.authBlockedMessage} Local pickups remain safe.',
                    style: const TextStyle(fontSize: 12, color: Color(0xFF93000A), fontWeight: FontWeight.w600),
                  ),
                ),

              // Hero Matte Charcoal Sync Status Card
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: UcoColors.charcoal,
                  borderRadius: BorderRadius.circular(26),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          isOnline ? 'NETWORK: ONLINE' : 'NETWORK: OFFLINE MODE',
                          style: const TextStyle(color: Color(0xFFA3A6A0), fontSize: 11, fontWeight: FontWeight.w700),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: UcoColors.charcoalSurface,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            '$_pendingEvents Pending Sync',
                            style: TextStyle(
                              color: _pendingEvents > 0 ? UcoColors.amber : UcoColors.lime,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Text(
                      _pendingEvents == 0
                          ? 'All Field Pickups Synced'
                          : '$_pendingEvents Event(s) • $_pendingPhotos Photo(s) Queued',
                      style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      _lastMasterSync != null
                          ? 'Master Sync: $_lastMasterSync'
                          : 'Master Data: Tap Sync Now to refresh vendors',
                      style: const TextStyle(color: Color(0xFFA3A6A0), fontSize: 11),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        ElevatedButton.icon(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: UcoColors.lime,
                            foregroundColor: UcoColors.ink,
                            shape: const StadiumBorder(),
                          ),
                          onPressed: SyncEngine.instance.isSyncing ? null : _triggerSync,
                          icon: const Icon(Icons.sync, size: 17),
                          label: Text(
                            SyncEngine.instance.isSyncing ? 'Syncing...' : 'Sync Now',
                            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 13),
                          ),
                        ),
                        CircleAvatar(
                          backgroundColor: Colors.white,
                          child: IconButton(
                            icon: const Icon(Icons.queue_outlined, color: UcoColors.ink, size: 19),
                            onPressed: () async {
                              await Navigator.of(context).push(
                                MaterialPageRoute(builder: (_) => const SyncQueueScreen()),
                              );
                              _loadData();
                            },
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // 2-Column KPI Cards
              Row(
                children: [
                  Expanded(
                    child: _KpiCard(
                      label: "Today's Pickups",
                      value: _todayCount.toString().padLeft(2, '0'),
                      icon: Icons.local_shipping_outlined,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _KpiCard(
                      label: 'Today Volume',
                      value: '${_todayLiters.toStringAsFixed(0)} L',
                      icon: Icons.water_drop_outlined,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              const Text('Recent Pickups', style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
              const SizedBox(height: 10),

              if (visible.isEmpty)
                Container(
                  padding: const EdgeInsets.all(28),
                  decoration: BoxDecoration(
                    color: UcoColors.card,
                    borderRadius: BorderRadius.circular(22),
                    border: Border.all(color: UcoColors.border),
                  ),
                  child: const Text(
                    'No pickups logged on this device yet. Tap "+ New Collection" below to capture a pickup.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: UcoColors.mutedInk, fontSize: 13),
                  ),
                )
              else
                ...visible.map((c) {
                  final synced = c.syncStatus == 'completed';
                  return Container(
                    margin: const EdgeInsets.only(bottom: 10),
                    decoration: BoxDecoration(
                      color: UcoColors.card,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: UcoColors.border),
                    ),
                    child: ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                      title: Text(c.vendorName, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14)),
                      subtitle: Text(
                        'CTN: ${c.containerQrCode} • ${c.volumeLiters.toStringAsFixed(1)} L • ${c.fieldFfaPercent != null ? '${c.fieldFfaPercent}% FFA' : 'No FFA'}',
                        style: const TextStyle(fontSize: 12, color: UcoColors.mutedInk),
                      ),
                      trailing: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: synced ? UcoColors.lime.withValues(alpha: 0.35) : const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          synced ? 'Synced' : 'Pending',
                          style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: UcoColors.ink),
                        ),
                      ),
                      onTap: () => Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => CollectionDetailScreen(collection: c)),
                      ),
                    ),
                  );
                }),
            ],
          ),
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerFloat,
      floatingActionButton: FloatingActionButton.extended(
        backgroundColor: UcoColors.charcoal,
        foregroundColor: Colors.white,
        shape: const StadiumBorder(),
        onPressed: () async {
          final created = await Navigator.of(context).push<bool>(
            MaterialPageRoute(builder: (_) => const NewCollectionScreen()),
          );
          if (created == true) _loadData();
        },
        icon: const CircleAvatar(
          radius: 13,
          backgroundColor: UcoColors.lime,
          child: Icon(Icons.add, color: UcoColors.ink, size: 18),
        ),
        label: const Text('New Collection', style: TextStyle(fontWeight: FontWeight.w700)),
      ),
    );
  }
}

class _KpiCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;

  const _KpiCard({required this.label, required this.value, required this.icon});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: UcoColors.card,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: UcoColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: const TextStyle(fontSize: 12, color: UcoColors.mutedInk, fontWeight: FontWeight.w600)),
              const CircleAvatar(radius: 4, backgroundColor: UcoColors.lime),
            ],
          ),
          const SizedBox(height: 10),
          Text(value, style: const TextStyle(fontSize: 26, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          CircleAvatar(
            radius: 16,
            backgroundColor: UcoColors.lime,
            child: Icon(icon, size: 17, color: UcoColors.ink),
          ),
        ],
      ),
    );
  }
}
