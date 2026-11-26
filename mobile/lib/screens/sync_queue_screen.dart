import 'package:flutter/material.dart';
import '../core/theme.dart';
import '../models/local_models.dart';
import '../services/local_database.dart';
import '../services/sync_engine.dart';
import 'collection_detail_screen.dart';

class SyncQueueScreen extends StatefulWidget {
  const SyncQueueScreen({super.key});

  @override
  State<SyncQueueScreen> createState() => _SyncQueueScreenState();
}

class _SyncQueueScreenState extends State<SyncQueueScreen> {
  List<OfflineCollection> _items = [];
  String _filter = 'all'; // all | pending | failed
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    SyncEngine.instance.addListener(_onSyncChange);
    _load();
  }

  @override
  void dispose() {
    SyncEngine.instance.removeListener(_onSyncChange);
    super.dispose();
  }

  void _onSyncChange() {
    if (mounted) _load();
  }

  Future<void> _load() async {
    final all = await LocalDatabase.instance.getCollections(onlyPending: true);
    if (!mounted) return;
    setState(() {
      _items = all;
      _loading = false;
    });
  }

  Future<void> _retryAll() async {
    final result = await SyncEngine.instance.syncAllPending();
    await _load();
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result.authError ?? 'Synced ${result.syncedEvents} event(s), ${result.failedEvents} remaining.',
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final filtered = _items.where((e) {
      if (_filter == 'failed') return e.syncStatus == 'failed';
      if (_filter == 'pending') return e.syncStatus != 'failed';
      return true;
    }).toList();

    final pendingPhotosCount = _items.fold<int>(
      0,
      (sum, item) => sum + item.photos.where((p) => p.uploadStatus != 'uploaded').length,
    );

    return Scaffold(
      backgroundColor: UcoColors.canvas,
      appBar: AppBar(
        title: const Text('Offline Sync Queue', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Chip(
              backgroundColor: UcoColors.charcoal,
              label: Text(
                SyncEngine.instance.isOnline ? 'Online' : 'Offline',
                style: TextStyle(
                  color: SyncEngine.instance.isOnline ? UcoColors.lime : UcoColors.amber,
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          // Matte Charcoal Summary Card
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: UcoColors.charcoal,
              borderRadius: BorderRadius.circular(24),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '${_items.length} Events Queued',
                        style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w800),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '$pendingPhotosCount Photos Pending Upload',
                        style: const TextStyle(color: Color(0xFFA3A6A0), fontSize: 12),
                      ),
                    ],
                  ),
                ),
                ElevatedButton.icon(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: UcoColors.lime,
                    foregroundColor: UcoColors.ink,
                    shape: const StadiumBorder(),
                  ),
                  onPressed: SyncEngine.instance.isSyncing ? null : _retryAll,
                  icon: const Icon(Icons.sync, size: 18),
                  label: Text(
                    SyncEngine.instance.isSyncing ? 'Syncing...' : 'Retry All',
                    style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Filter Chips
          Row(
            children: [
              _filterPill('All (${_items.length})', 'all'),
              const SizedBox(width: 8),
              _filterPill('Pending', 'pending'),
              const SizedBox(width: 8),
              _filterPill('Failed', 'failed'),
            ],
          ),
          const SizedBox(height: 14),

          if (_loading)
            const Center(child: Padding(padding: EdgeInsets.all(32), child: CircularProgressIndicator()))
          else if (filtered.isEmpty)
            Container(
              padding: const EdgeInsets.all(30),
              decoration: BoxDecoration(
                color: UcoColors.card,
                borderRadius: BorderRadius.circular(22),
              ),
              child: const Text(
                'Queue is empty — all captured pickups are synced.',
                textAlign: TextAlign.center,
                style: TextStyle(color: UcoColors.mutedInk, fontSize: 13),
              ),
            )
          else
            ...filtered.map((item) {
              final eventSynced = item.syncStatus == 'event_synced';
              final uploadedCount = item.photos.where((p) => p.uploadStatus == 'uploaded').length;
              return Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: UcoColors.card,
                  borderRadius: BorderRadius.circular(22),
                  border: Border.all(color: UcoColors.border),
                ),
                child: InkWell(
                  onTap: () => Navigator.of(context).push(
                    MaterialPageRoute(builder: (_) => CollectionDetailScreen(collection: item)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(item.vendorName, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 14)),
                          ),
                          Text('${item.volumeLiters.toStringAsFixed(1)} L', style: const TextStyle(fontWeight: FontWeight.w800)),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'CTN: ${item.containerQrCode} • ${item.collectedAt}',
                        style: const TextStyle(fontSize: 11, color: UcoColors.mutedInk),
                      ),
                      const SizedBox(height: 10),
                      Wrap(
                        spacing: 8,
                        runSpacing: 6,
                        children: [
                          _stepBadge(
                            eventSynced ? 'Event Synced ✓' : 'Event Pending',
                            eventSynced ? UcoColors.lime : UcoColors.canvas,
                          ),
                          _stepBadge(
                            'Photos $uploadedCount/${item.photos.length}',
                            uploadedCount == item.photos.length ? UcoColors.lime : const Color(0xFFFEF3C7),
                          ),
                        ],
                      ),
                      if (item.syncError != null) ...[
                        const SizedBox(height: 8),
                        Text(item.syncError!, style: const TextStyle(fontSize: 11, color: UcoColors.danger)),
                      ],
                    ],
                  ),
                ),
              );
            }),
        ],
      ),
    );
  }

  Widget _filterPill(String label, String value) {
    final active = _filter == value;
    return ChoiceChip(
      label: Text(label),
      selected: active,
      selectedColor: UcoColors.charcoal,
      backgroundColor: UcoColors.card,
      labelStyle: TextStyle(
        color: active ? UcoColors.lime : UcoColors.ink,
        fontWeight: FontWeight.w700,
        fontSize: 12,
      ),
      shape: const StadiumBorder(),
      side: const BorderSide(color: UcoColors.border),
      onSelected: (_) => setState(() => _filter = value),
    );
  }

  Widget _stepBadge(String text, Color bg) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(999)),
      child: Text(text, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: UcoColors.ink)),
    );
  }
}
