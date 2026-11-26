<?php

namespace Tests\Feature;

use App\Models\CollectionEvent;
use App\Models\Container;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@ucotrace.gh',
            'password' => Hash::make('Admin123!'),
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_login_and_view_clean_empty_dashboard(): void
    {
        $admin = $this->createAdmin();

        $this->post('/login', [
            'email' => 'admin@ucotrace.gh',
            'password' => 'Admin123!',
        ])->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Field Capture Overview')
            ->assertSee('No collectors created yet.');
    }

    public function test_collector_is_forbidden_from_admin_console(): void
    {
        $collector = User::create([
            'name' => 'Collector',
            'email' => 'col@ucotrace.gh',
            'phone' => '+233241111111',
            'password' => Hash::make('Secret123!'),
            'role' => User::ROLE_COLLECTOR,
            'is_active' => true,
        ]);

        $this->actingAs($collector)
            ->get(route('admin.dashboard'))
            ->assertStatus(403);
    }

    public function test_admin_can_crud_vendor_generate_qr_batch_and_print_pdf(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'vendor_code' => 'VND-001',
            'name' => 'Osu Demo Kitchen',
            'tier' => 'restaurant',
            'gps_lat' => 5.5560,
            'gps_lng' => -0.1820,
            'mou_status' => 'mou_active',
            'is_active' => '1',
            'contact_names' => ['Ama Mensah'],
            'contact_roles' => ['Manager'],
            'contact_phones' => ['+233244000111'],
        ])->assertRedirect(route('admin.vendors.index'));

        $vendor = Vendor::where('vendor_code', 'VND-001')->firstOrFail();
        $this->assertEquals('Ama Mensah', $vendor->contact_chain_json[0]['name']);

        $this->actingAs($admin)->post(route('admin.containers.batch'), [
            'prefix' => 'QP-20L',
            'qualiplast_tier' => '20L Jerrycan',
            'capacity_liters' => 20,
            'quantity' => 3,
            'assigned_vendor_id' => $vendor->id,
        ])->assertRedirect(route('admin.containers.index'));

        $this->assertEquals(3, Container::count());

        $this->actingAs($admin)
            ->get(route('admin.containers.print-qr'))
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_deactivating_collector_revokes_sanctum_tokens_and_admin_can_log_correction(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.collectors.store'), [
            'name' => 'Kwame',
            'email' => 'kwame@ucotrace.gh',
            'phone' => '+233249999999',
            'password' => 'Password123!',
        ])->assertRedirect(route('admin.collectors.index'));

        $collector = User::where('email', 'kwame@ucotrace.gh')->firstOrFail();
        $collector->createToken('mobile');
        $this->assertEquals(1, $collector->tokens()->count());

        // Deactivate revokes tokens
        $this->actingAs($admin)->put(route('admin.collectors.update', $collector), [
            'name' => 'Kwame',
            'email' => 'kwame@ucotrace.gh',
            'phone' => '+233249999999',
            'is_active' => '0',
        ])->assertRedirect(route('admin.collectors.index'));

        $this->assertEquals(0, $collector->fresh()->tokens()->count());

        // Create event and log append-only correction
        $vendor = Vendor::create([
            'vendor_code' => 'VND-010',
            'name' => 'Labadi Kitchen',
            'tier' => 'hotel',
            'gps_lat' => 5.5600,
            'gps_lng' => -0.1500,
            'mou_status' => 'loi',
        ]);
        $container = Container::create([
            'qr_code' => 'QP-50L-001',
            'qualiplast_tier' => '50L Drum',
            'capacity_liters' => 50,
            'assigned_vendor_id' => $vendor->id,
            'status' => 'active',
        ]);
        $original = CollectionEvent::create([
            'client_event_uuid' => (string) Str::uuid(),
            'vendor_id' => $vendor->id,
            'collector_id' => $collector->id,
            'container_id' => $container->id,
            'scan_method' => 'qr_scan',
            'collected_at' => now(),
            'synced_at' => now(),
            'gps_lat' => 5.5600,
            'gps_lng' => -0.1500,
            'volume_liters' => 450.0,
            'field_device_type' => 'testo',
            'payment_amount' => 300.0,
            'payment_method' => 'cash',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.events.correction', $original), [
                'volume_liters' => 45.0,
                'field_ffa_percent' => 2.5,
                'payment_amount' => 300.0,
                'payment_method' => 'cash',
                'correction_reason' => 'Typo during pickup entry: 450L instead of 45L.',
            ])->assertRedirect();

        $this->assertEquals(2, CollectionEvent::count());
        $correction = CollectionEvent::where('supersedes_event_id', $original->id)->firstOrFail();
        $this->assertEquals(45.0, $correction->volume_liters);
        $this->assertEquals($admin->id, $correction->created_by_admin_id);
    }
}
