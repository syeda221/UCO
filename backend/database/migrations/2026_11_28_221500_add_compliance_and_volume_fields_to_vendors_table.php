<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('area_zone', 120)->nullable()->after('address')->index();
            $table->decimal('expected_monthly_volume', 10, 2)->nullable()->after('qualiplast_tier');
            $table->string('expected_volume_unit', 10)->default('liters')->after('expected_monthly_volume');
            $table->boolean('produces_10_plus_tonnes_monthly')->default(false)->after('expected_volume_unit')->index();
            $table->boolean('has_animal_origin')->default(false)->after('produces_10_plus_tonnes_monthly')->index();
            $table->string('iscc_declaration_status', 20)->default('none')->after('has_animal_origin')->index();
            $table->date('iscc_signed_date')->nullable()->after('iscc_declaration_status');
            $table->date('iscc_expiry_date')->nullable()->after('iscc_signed_date')->index();
            $table->string('iscc_doc_path', 255)->nullable()->after('iscc_expiry_date');
            $table->string('iscc_doc_name', 180)->nullable()->after('iscc_doc_path');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['area_zone']);
            $table->dropIndex(['produces_10_plus_tonnes_monthly']);
            $table->dropIndex(['has_animal_origin']);
            $table->dropIndex(['iscc_declaration_status']);
            $table->dropIndex(['iscc_expiry_date']);

            $table->dropColumn([
                'area_zone',
                'expected_monthly_volume',
                'expected_volume_unit',
                'produces_10_plus_tonnes_monthly',
                'has_animal_origin',
                'iscc_declaration_status',
                'iscc_signed_date',
                'iscc_expiry_date',
                'iscc_doc_path',
                'iscc_doc_name',
            ]);
        });
    }
};
