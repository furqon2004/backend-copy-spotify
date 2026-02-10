<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('song_ai_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('song_id')->constrained('songs')->onDelete('cascade');
            $table->json('mood_tags')->nullable();
            $table->integer('bpm')->nullable();
            $table->string('key_signature')->nullable();
            $table->decimal('energy_score', 3, 2)->nullable();
            $table->timestamps();

            $table->index('bpm');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('song_ai_metadata');
    }
};