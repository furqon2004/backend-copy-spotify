<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('song_genres', function (Blueprint $table) {
            $table->foreignUuid('song_id')->constrained('songs')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete();
            $table->primary(['song_id', 'genre_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_genres');
    }
};