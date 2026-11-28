<?php

namespace Tests\Feature;

use App\Models\CollectionEvent;
use App\Models\Container;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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

        // Admin can also sync vendor GPS from a verified field pickup
        $this->actingAs($admin)
            ->post(route('admin.events.set-vendor-gps', $correction))
            ->assertRedirect();
        $this->assertEquals($correction->gps_lat, $vendor->fresh()->gps_lat);
    }

    public function test_admin_can_manage_extended_vendor_compliance_iscc_docs_and_filters(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();

        $isccFile = UploadedFile::fake()->create('iscc-signed.pdf', 80, 'application/pdf');
        $permitFile = UploadedFile::fake()->create('epa-permit.pdf', 120, 'application/pdf');

        $this->actingAs($admin)->post(route('admin.vendors.store'), [
            'vendor_code' => 'VND-020',
            'name' => 'Airport Kempinski Kitchen',
            'legal_name' => 'Kempinski Gold Coast Ltd',
            'tier' => 'hotel',
            'address' => 'Gamel Abdul Nasser Ave',
            'area_zone' => 'Airport Residential',
            'gps_lat' => 5.5592,
            'gps_lng' => -0.1973,
            'mou_status' => 'mou_active',
            'mou_expiry_date' => now()->addMonths(6)->toDateString(),
            'qualiplast_tier' => '50L Drum',
            'expected_monthly_volume' => 11500,
            'expected_volume_unit' => 'kg',
            'produces_10_plus_tonnes_monthly' => '1',
            'has_animal_origin' => '1',
            'iscc_declaration_status' => 'active',
            'iscc_signed_date' => now()->subMonths(11)->toDateString(),
            'iscc_expiry_date' => now()->addDays(7)->toDateString(),
            'iscc_doc' => $isccFile,
            'doc_type' => 'environmental_permit',
            'doc_reference_number' => 'EPA-GH-9901',
            'doc_issue_date' => now()->subMonths(6)->toDateString(),
            'doc_expiry_date' => now()->addDays(10)->toDateString(),
            'onboarding_docs' => [$permitFile],
            'is_active' => '1',
            'contact_names' => ['Kofi Boateng'],
            'contact_roles' => ['Executive Chef'],
            'contact_phones' => ['+233245000222'],
            'contact_emails' => ['chef@kempinski.gh'],
        ])->assertRedirect(route('admin.vendors.index'));

        $vendor = Vendor::where('vendor_code', 'VND-020')->firstOrFail();
        $this->assertEquals('Airport Residential', $vendor->area_zone);
        $this->assertEquals(11500.0, $vendor->expected_monthly_volume);
        $this->assertEquals('kg', $vendor->expected_volume_unit);
        $this->assertTrue($vendor->produces_10_plus_tonnes_monthly);
        $this->assertTrue($vendor->has_animal_origin);
        $this->assertTrue($vendor->isIsccExpiringSoon(15));
        $this->assertFalse($vendor->isIsccExpired());
        $this->assertNotNull($vendor->iscc_doc_path);
        $this->assertEquals('chef@kempinski.gh', $vendor->contact_chain_json[0]['email']);

        $this->assertEquals(2, $vendor->documents()->count());
        $permitDoc = $vendor->documents()->where('doc_type', 'environmental_permit')->firstOrFail();

        // Download ISCC declaration and permit document
        $this->actingAs($admin)->get(route('admin.vendors.iscc-doc', $vendor))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.vendors.documents.download', [$vendor, $permitDoc]))->assertStatus(200);

        // Renew/replace existing permit document via standalone document upload
        $renewedPermit = UploadedFile::fake()->create('epa-permit-renewed.pdf', 140, 'application/pdf');
        $this->actingAs($admin)->post(route('admin.vendors.documents.store', $vendor), [
            'doc_type' => 'environmental_permit',
            'doc_reference_number' => 'EPA-GH-2027-01',
            'doc_issue_date' => now()->toDateString(),
            'doc_expiry_date' => now()->addYear()->toDateString(),
            'replace_document_id' => $permitDoc->id,
            'document_file' => $renewedPermit,
        ])->assertRedirect(route('admin.vendors.edit', $vendor));

        $newDoc = VendorDocument::where('reference_number', 'EPA-GH-2027-01')->firstOrFail();
        $this->assertEquals('replaced', $permitDoc->fresh()->status);
        $this->assertEquals($newDoc->id, $permitDoc->fresh()->replaced_by_document_id);

        // Mark renewed doc expired and verify edit view + filters + dashboard alerts
        $this->actingAs($admin)
            ->post(route('admin.vendors.documents.expire', [$vendor, $newDoc]))
            ->assertRedirect(route('admin.vendors.edit', $vendor));
        $this->assertEquals('expired', $newDoc->fresh()->status);

        $this->actingAs($admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertStatus(200)
            ->assertSee('Vendor Performance & Compliance Snapshot')
            ->assertSee('Animal Origin Declared')
            ->assertSee('10+ Tonnes/Mo Producer')
            ->assertSee('EPA-GH-2027-01');

        $this->actingAs($admin)
            ->get(route('admin.vendors.index', ['compliance_flag' => 'expiring_attention']))
            ->assertStatus(200)
            ->assertSee('Airport Kempinski Kitchen');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('ISCC Self-Declaration records are expired or expiring within 15 days');

        // Unchecking booleans resets them cleanly while preserving existing ISCC file
        $existingIsccPath = $vendor->iscc_doc_path;
        $this->actingAs($admin)->put(route('admin.vendors.update', $vendor), [
            'vendor_code' => 'VND-020',
            'name' => 'Airport Kempinski Kitchen',
            'tier' => 'hotel',
            'area_zone' => 'Airport Residential',
            'gps_lat' => 5.5592,
            'gps_lng' => -0.1973,
            'mou_status' => 'mou_active',
            'expected_volume_unit' => 'liters',
            'produces_10_plus_tonnes_monthly' => '0',
            'has_animal_origin' => '0',
            'iscc_declaration_status' => 'active',
            'is_active' => '1',
            'contact_names' => ['Kofi Boateng'],
            'contact_roles' => ['Executive Chef'],
            'contact_phones' => ['+233245000222'],
            'contact_emails' => ['chef@kempinski.gh'],
        ])->assertRedirect(route('admin.vendors.index'));

        $freshVendor = $vendor->fresh();
        $this->assertFalse($freshVendor->produces_10_plus_tonnes_monthly);
        $this->assertFalse($freshVendor->has_animal_origin);
        $this->assertEquals($existingIsccPath, $freshVendor->iscc_doc_path);
    }
}
