<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stream_history', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('song_id')->constrained('songs')->cascadeOnDelete();
            $table->timestamp('played_at')->useCurrent();
            $table->unsignedInteger('duration_played_ms');
            $table->enum('source', ['PLAYLIST', 'SEARCH', 'AI_RECOMMENDATION'])->index();
            $table->string('device', 255)->nullable();

            $table->index(['user_id', 'played_at']);
            $table->index('song_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_history');
    }
};