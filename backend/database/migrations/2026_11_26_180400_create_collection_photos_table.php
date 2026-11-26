<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_event_id')
                ->constrained('collection_events')
                ->restrictOnDelete();
            $table->string('photo_type', 30); // pickup_evidence | ffa_reading
            $table->string('file_path', 255);
            $table->char('sha256_hash', 64);
            $table->unsignedInteger('file_size_bytes')->nullable();
            $table->timestamp('captured_at')->nullable();
            // Append-only: no updated_at column.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_photos');
    }
};
