<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('doc_type', 40)->index(); // mou | loi | iscc_self_declaration | environmental_permit | business_license | other
            $table->string('reference_number', 80)->nullable()->index();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable()->index();
            $table->string('file_path', 255);
            $table->string('file_name', 180);
            $table->string('status', 20)->default('active')->index(); // active | expired | replaced
            $table->unsignedBigInteger('replaced_by_document_id')->nullable()->index();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_documents');
    }
};
