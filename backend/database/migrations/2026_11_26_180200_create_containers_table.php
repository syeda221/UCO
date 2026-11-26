<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code', 64)->unique();
            $table->string('qualiplast_tier', 60);
            $table->decimal('capacity_liters', 8, 2)->default(20.00);
            $table->foreignId('assigned_vendor_id')
                ->nullable()
                ->constrained('vendors')
                ->nullOnDelete();
            $table->string('status', 20)->default('active')->index(); // active | damaged | retired
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
