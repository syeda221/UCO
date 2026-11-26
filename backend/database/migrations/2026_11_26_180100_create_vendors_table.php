<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_code', 32)->unique();
            $table->string('name', 150);
            $table->string('legal_name', 180)->nullable();
            $table->string('tier', 20)->index(); // hotel | restaurant | street
            $table->string('address', 255)->nullable();
            $table->decimal('gps_lat', 10, 7);
            $table->decimal('gps_lng', 10, 7);
            $table->string('mou_status', 20)->default('none')->index(); // none | loi | mou_active | expired
            $table->date('mou_expiry_date')->nullable()->index();
            $table->string('qualiplast_tier', 60)->nullable();
            $table->json('contact_chain_json')->nullable();
            $table->json('onboarding_docs_json')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
