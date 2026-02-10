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
        Schema::create('podcasts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('artist_id')->index()->constrained('artists')->cascadeOnDelete();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('category')->index();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('podcast');
    }
};
