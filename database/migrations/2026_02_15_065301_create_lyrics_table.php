<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lyrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('song_id')->unique()->constrained('songs')->cascadeOnDelete();
            $table->longText('content'); // Full lyrics text
            $table->json('synced_lyrics')->nullable(); // Timestamped lyrics [{time: 0, text: "..."}, ...]
            $table->string('language', 10)->default('en');
            $table->string('source')->nullable(); // e.g. "manual", "genius", "musixmatch"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lyrics');
    }
};
