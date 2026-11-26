<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('action', 60)->index();
            $table->string('entity_type', 60)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('metadata_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            // Append-only: no updated_at column.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
