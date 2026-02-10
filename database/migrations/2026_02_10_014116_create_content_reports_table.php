<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->enum('target_type', ['SONG', 'PLAYLIST', 'USER']);
            $table->uuid('target_id');
            $table->text('reason');
            $table->enum('status', ['PENDING', 'RESOLVED', 'REJECTED'])->default('PENDING')->index();
            $table->timestamp('created_at')->useCurrent()->index();

            $table->index(['target_type', 'target_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
    }
};