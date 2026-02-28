<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playlist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('playlist_id')->index()->constrained('playlists')->cascadeOnDelete();
            $table->foreignUuid('song_id')->index()->constrained('songs')->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_items');
    }
};