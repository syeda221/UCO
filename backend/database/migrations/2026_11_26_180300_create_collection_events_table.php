<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_events', function (Blueprint $table) {
            $table->id();
            $table->char('client_event_uuid', 36)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('container_id')->constrained('containers')->restrictOnDelete();

            $table->string('scan_method', 20)->default('qr_scan')->index(); // qr_scan | manual_fallback
            $table->string('fallback_reason', 255)->nullable();
            $table->boolean('container_mismatch_flag')->default(false)->index();

            $table->timestamp('collected_at')->index();
            $table->timestamp('synced_at')->useCurrent();

            $table->decimal('gps_lat', 10, 7);
            $table->decimal('gps_lng', 10, 7);
            $table->decimal('gps_accuracy_meters', 8, 2)->nullable();
            $table->boolean('gps_is_mocked')->default(false)->index();
            $table->decimal('gps_distance_meters', 10, 2)->nullable();
            $table->boolean('gps_mismatch_flag')->default(false)->index();

            $table->decimal('volume_liters', 8, 2);
            $table->decimal('field_ffa_percent', 5, 2)->nullable();
            $table->decimal('field_miu_percent', 5, 2)->nullable();
            $table->string('field_device_type', 30); // colorimetric_kit | testo | ebro

            $table->decimal('payment_amount', 10, 2)->default(0.00);
            $table->string('payment_method', 20)->default('cash'); // cash | momo | bank | credit
            $table->string('payment_reference', 100)->nullable();

            $table->foreignId('supersedes_event_id')
                ->nullable()
                ->constrained('collection_events')
                ->restrictOnDelete();
            $table->text('correction_reason')->nullable();
            $table->foreignId('created_by_admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Append-only: no updated_at column.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_events');
    }
};
