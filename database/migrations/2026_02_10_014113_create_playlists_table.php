<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->index()->constrained('users')->cascadeOnDelete();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('cover_url')->nullable();
            $table->boolean('is_ai_generated')->default(false)->index();
            $table->text('ai_prompt_used')->nullable();
            $table->boolean('is_public')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlists');
    }
};