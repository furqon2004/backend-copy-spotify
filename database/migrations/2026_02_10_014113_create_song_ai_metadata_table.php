<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('song_ai_metadata', function (Blueprint $table) {
            $table->foreignUuid('song_id')->primary()->constrained('songs')->cascadeOnDelete();
            $table->string('vector_id')->nullable()->index();
            $table->float('bpm')->nullable();
            $table->string('key_signature', 10)->nullable();
            $table->json('mood_tags')->nullable();
            $table->float('danceability')->nullable();
            $table->float('energy')->nullable();
            $table->float('valence')->nullable();
            $table->timestamp('last_analyzed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_ai_metadata');
    }
};