<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CollectionEvent;
use App\Models\CollectionPhoto;
use App\Models\Container;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class CollectionSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createCollector(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Field Collector',
            'email' => 'collector@ucotrace.gh',
            'phone' => '+233240000001',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_COLLECTOR,
            'is_active' => true,
        ], $overrides));
    }

    private function createVendor(array $overrides = []): Vendor
    {
        return Vendor::create(array_merge([
            'vendor_code' => 'VND-001',
            'name' => 'Accra Test Kitchen',
            'tier' => 'restaurant',
            'gps_lat' => 5.6037000,
            'gps_lng' => -0.1870000,
            'mou_status' => 'mou_active',
            'is_active' => true,
        ], $overrides));
    }

    private function createContainer(Vendor $vendor, array $overrides = []): Container
    {
        return Container::create(array_merge([
            'qr_code' => 'QP-20L-001',
            'qualiplast_tier' => '20L Jerrycan',
            'capacity_liters' => 20.0,
            'assigned_vendor_id' => $vendor->id,
            'status' => 'active',
        ], $overrides));
    }

    public function test_seeder_only_creates_admin_with_no_fake_operational_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(1, User::count());
        $this->assertEquals(User::ROLE_ADMIN, User::first()->role);
        $this->assertEquals(0, Vendor::count());
        $this->assertEquals(0, Container::count());
        $this->assertEquals(0, CollectionEvent::count());
    }

    public function test_collector_can_login_with_phone_or_email_and_blocked_when_inactive(): void
    {
        $collector = $this->createCollector();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@ucotrace.gh',
            'password' => Hash::make('Admin123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        // Phone login succeeds for collector
        $res = $this->postJson('/api/v1/auth/login', [
            'identifier' => '+233240000001',
            'password' => 'Secret123!',
        ]);
        $res->assertStatus(200)->assertJsonStructure(['token', 'collector' => ['id', 'name', 'role']]);

        // Admin cannot login to collector mobile API
        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin@ucotrace.gh',
            'password' => 'Admin123!',
        ])->assertStatus(403);

        // Deactivated collector rejected
        $collector->update(['is_active' => false]);
        $this->postJson('/api/v1/auth/login', [
            'identifier' => 'collector@ucotrace.gh',
            'password' => 'Secret123!',
        ])->assertStatus(403);
    }

    public function test_offline_collection_sync_is_idempotent_and_flags_gps_and_container_mismatches(): void
    {
        $collector = $this->createCollector();
        $vendorA = $this->createVendor();
        $vendorB = $this->createVendor([
            'vendor_code' => 'VND-002',
            'name' => 'Osu Street Stall',
            'tier' => 'street',
            'gps_lat' => 5.5560000,
            'gps_lng' => -0.1820000,
        ]);
        $containerA = $this->createContainer($vendorA);

        $uuid = (string) Str::uuid();
        $payload = [
            'client_event_uuid' => $uuid,
            'vendor_id' => $vendorB->id,
            'collector_id' => 9999, // spoof attempt should be ignored
            'container_qr_code' => $containerA->qr_code, // belongs to vendorA -> mismatch!
            'scan_method' => 'qr_scan',
            'collected_at' => now()->subMinutes(15)->toIso8601String(),
            'gps_lat' => 5.5600000, // ~444m away from vendorB -> >200m flag!
            'gps_lng' => -0.1820000,
            'gps_accuracy_meters' => 8.5,
            'gps_is_mocked' => false,
            'volume_liters' => 18.5,
            'field_ffa_percent' => 2.4,
            'field_miu_percent' => 0.6,
            'field_device_type' => 'testo',
            'payment_amount' => 140.0,
            'payment_method' => 'momo',
            'payment_reference' => 'MM-10023',
        ];

        $first = $this->actingAs($collector, 'sanctum')
            ->postJson('/api/v1/collections/sync', $payload);

        $first->assertStatus(201)
            ->assertJsonPath('status', 'created')
            ->assertJsonPath('event.gps_mismatch_flag', true)
            ->assertJsonPath('event.container_mismatch_flag', true);

        $event = CollectionEvent::where('client_event_uuid', $uuid)->firstOrFail();
        $this->assertEquals($collector->id, $event->collector_id);
        $this->assertGreaterThan(200.0, $event->gps_distance_meters);

        // Retry with same client_event_uuid -> idempotent 200, no duplicate!
        $retry = $this->actingAs($collector, 'sanctum')
            ->postJson('/api/v1/collections/sync', $payload);

        $retry->assertStatus(200)->assertJsonPath('status', 'already_synced');
        $this->assertEquals(1, CollectionEvent::where('client_event_uuid', $uuid)->count());
    }

    public function test_manual_qr_fallback_requires_reason(): void
    {
        $collector = $this->createCollector();
        $vendor = $this->createVendor();
        $container = $this->createContainer($vendor);

        $basePayload = [
            'client_event_uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'container_id' => $container->id,
            'scan_method' => 'manual_fallback',
            'collected_at' => now()->toIso8601String(),
            'gps_lat' => 5.6037000,
            'gps_lng' => -0.1870000,
            'volume_liters' => 20.0,
            'field_device_type' => 'colorimetric_kit',
            'payment_amount' => 100.0,
            'payment_method' => 'cash',
        ];

        // Missing reason -> 422
        $this->actingAs($collector, 'sanctum')
            ->postJson('/api/v1/collections/sync', $basePayload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fallback_reason']);

        // With reason -> 201
        $basePayload['fallback_reason'] = 'QR sticker covered in oil and scratched';
        $this->actingAs($collector, 'sanctum')
            ->postJson('/api/v1/collections/sync', $basePayload)
            ->assertStatus(201);
    }

    public function test_photo_upload_verifies_sha256_is_idempotent_and_isolates_collectors(): void
    {
        Storage::fake('local');

        $collector1 = $this->createCollector();
        $collector2 = $this->createCollector([
            'email' => 'collector2@ucotrace.gh',
            'phone' => '+233240000002',
        ]);
        $vendor = $this->createVendor();
        $container = $this->createContainer($vendor);
        $uuid = (string) Str::uuid();

        $this->actingAs($collector1, 'sanctum')->postJson('/api/v1/collections/sync', [
            'client_event_uuid' => $uuid,
            'vendor_id' => $vendor->id,
            'container_id' => $container->id,
            'scan_method' => 'qr_scan',
            'collected_at' => now()->toIso8601String(),
            'gps_lat' => 5.6037,
            'gps_lng' => -0.1870,
            'volume_liters' => 15.0,
            'field_device_type' => 'ebro',
        ])->assertStatus(201);

        $file = UploadedFile::fake()->image('pickup.jpg', 800, 600);
        $realHash = hash_file('sha256', $file->getRealPath());

        // Collector 2 cannot upload to collector 1's event
        $this->actingAs($collector2, 'sanctum')
            ->post("/api/v1/collections/{$uuid}/photos", [
                'photo' => $file,
                'photo_type' => 'pickup_evidence',
                'sha256_hash' => $realHash,
            ])->assertStatus(403);

        // Bad SHA-256 rejected
        $this->actingAs($collector1, 'sanctum')
            ->postJson("/api/v1/collections/{$uuid}/photos", [
                'photo' => $file,
                'photo_type' => 'pickup_evidence',
                'sha256_hash' => str_repeat('a', 64),
            ])->assertStatus(422);

        // Valid upload succeeds
        $this->actingAs($collector1, 'sanctum')
            ->postJson("/api/v1/collections/{$uuid}/photos", [
                'photo' => $file,
                'photo_type' => 'pickup_evidence',
                'sha256_hash' => $realHash,
            ])->assertStatus(201)->assertJsonPath('status', 'uploaded');

        // Retry same photo -> idempotent 200
        $this->actingAs($collector1, 'sanctum')
            ->postJson("/api/v1/collections/{$uuid}/photos", [
                'photo' => $file,
                'photo_type' => 'pickup_evidence',
                'sha256_hash' => $realHash,
            ])->assertStatus(200)->assertJsonPath('status', 'already_uploaded');

        $this->assertEquals(1, CollectionPhoto::count());
    }

    public function test_custody_records_are_strictly_append_only(): void
    {
        $collector = $this->createCollector();
        $vendor = $this->createVendor();
        $container = $this->createContainer($vendor);

        $event = CollectionEvent::create([
            'client_event_uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'collector_id' => $collector->id,
            'container_id' => $container->id,
            'scan_method' => 'qr_scan',
            'collected_at' => now(),
            'synced_at' => now(),
            'gps_lat' => 5.6037,
            'gps_lng' => -0.1870,
            'volume_liters' => 20.0,
            'field_device_type' => 'colorimetric_kit',
            'created_at' => now(),
        ]);

        // Eloquent update blocked
        $this->expectException(LogicException::class);
        $event->update(['volume_liters' => 999.0]);
    }

    public function test_database_triggers_block_raw_updates_and_deletes_on_custody_tables(): void
    {
        $collector = $this->createCollector();
        $vendor = $this->createVendor();
        $container = $this->createContainer($vendor);

        $event = CollectionEvent::create([
            'client_event_uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'collector_id' => $collector->id,
            'container_id' => $container->id,
            'scan_method' => 'qr_scan',
            'collected_at' => now(),
            'synced_at' => now(),
            'gps_lat' => 5.6037,
            'gps_lng' => -0.1870,
            'volume_liters' => 20.0,
            'field_device_type' => 'colorimetric_kit',
            'created_at' => now(),
        ]);

        $audit = AuditLog::create([
            'actor_user_id' => $collector->id,
            'action' => 'test_action',
            'entity_type' => 'collection_event',
            'entity_id' => $event->id,
            'created_at' => now(),
        ]);

        $updateBlocked = false;
        try {
            DB::table('collection_events')->where('id', $event->id)->update(['volume_liters' => 500]);
        } catch (\Throwable) {
            $updateBlocked = true;
        }
        $this->assertTrue($updateBlocked);

        $deleteBlocked = false;
        try {
            DB::table('audit_logs')->where('id', $audit->id)->delete();
        } catch (\Throwable) {
            $deleteBlocked = true;
        }
        $this->assertTrue($deleteBlocked);
    }
}
