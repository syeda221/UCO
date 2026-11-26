import 'dart:io';
import 'package:flutter/material.dart';
import '../core/theme.dart';
import '../models/local_models.dart';

class CollectionDetailScreen extends StatelessWidget {
  final OfflineCollection collection;

  const CollectionDetailScreen({super.key, required this.collection});

  @override
  Widget build(BuildContext context) {
    final isSynced = collection.syncStatus == 'completed';

    return Scaffold(
      backgroundColor: UcoColors.canvas,
      appBar: AppBar(
        title: const Text('Collection Detail', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 14),
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: UcoColors.charcoal,
              borderRadius: BorderRadius.circular(999),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.verified_user, color: UcoColors.lime, size: 14),
                SizedBox(width: 5),
                Text('Read-Only', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ],
      ),
      body: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          // Locked GPS Hero Card
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: UcoColors.charcoal,
              borderRadius: BorderRadius.circular(24),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('LOCKED GPS COORDINATES', style: TextStyle(color: Color(0xFFA3A6A0), fontSize: 11)),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: isSynced ? UcoColors.lime : UcoColors.amber,
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        isSynced ? 'Synced' : 'Pending Sync',
                        style: const TextStyle(color: UcoColors.ink, fontSize: 11, fontWeight: FontWeight.w700),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  '${collection.gpsLat.toStringAsFixed(6)}, ${collection.gpsLng.toStringAsFixed(6)}',
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 6),
                Text(
                  'Accuracy: ±${collection.gpsAccuracyMeters?.toStringAsFixed(1) ?? '?'}m • Captured: ${collection.collectedAt}',
                  style: const TextStyle(color: Color(0xFFA3A6A0), fontSize: 11),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Vendor & Container
          _InfoCard(
            title: 'Vendor & Container',
            children: [
              _kv('Vendor', '${collection.vendorName} (${collection.vendorTier})'),
              _kv('Container QR', '${collection.containerQrCode} • ${collection.containerTier}'),
              _kv(
                'Scan Method',
                collection.scanMethod == 'manual_fallback'
                    ? 'Manual Fallback (${collection.fallbackReason ?? ''})'
                    : 'QR Scanned',
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Volume & Quality
          _InfoCard(
            title: 'Volume & Field Quality',
            children: [
              _kv('Volume', '${collection.volumeLiters.toStringAsFixed(1)} L'),
              _kv('Device Used', collection.fieldDeviceType.replaceAll('_', ' ').toUpperCase()),
              _kv('Field FFA %', collection.fieldFfaPercent != null ? '${collection.fieldFfaPercent}%' : 'N/A'),
              _kv('Field MIU %', collection.fieldMiuPercent != null ? '${collection.fieldMiuPercent}%' : 'N/A'),
            ],
          ),
          const SizedBox(height: 14),

          // Payment
          _InfoCard(
            title: 'Payment Record',
            children: [
              _kv('Amount', 'GHS ${collection.paymentAmount.toStringAsFixed(2)}'),
              _kv('Method', collection.paymentMethod.toUpperCase()),
              if (collection.paymentReference != null && collection.paymentReference!.isNotEmpty)
                _kv('Reference', collection.paymentReference!),
            ],
          ),
          const SizedBox(height: 14),

          // Photos
          _InfoCard(
            title: 'Photo Evidence (${collection.photos.length})',
            children: collection.photos.map((photo) {
              final exists = File(photo.localFilePath).existsSync();
              return Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    if (exists)
                      ClipRRect(
                        borderRadius: BorderRadius.circular(14),
                        child: Image.file(
                          File(photo.localFilePath),
                          height: 160,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),
                    const SizedBox(height: 6),
                    Text(
                      '${photo.photoType} • ${photo.uploadStatus.toUpperCase()}',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                    ),
                    Text(
                      'SHA-256: ${photo.sha256Hash}',
                      style: const TextStyle(fontSize: 10, color: UcoColors.mutedInk),
                    ),
                  ],
                ),
              );
            }).toList(),
          ),
        ],
      ),
    );
  }

  Widget _kv(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 110,
            child: Text(label, style: const TextStyle(fontSize: 12, color: UcoColors.mutedInk)),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
          ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _InfoCard({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: UcoColors.card,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: UcoColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800)),
          const SizedBox(height: 10),
          ...children,
        ],
      ),
    );
  }
}
