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
        Schema::create('podcast_episodes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('podcast_id')->index()->constrained('podcasts')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_url');
            $table->unsignedInteger('duration_ms');
            $table->unsignedBigInteger('stream_count')->default(0);
            $table->date('release_date')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcast_episodes');
    }
};
