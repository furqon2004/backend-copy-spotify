<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('artist_id')->index()->constrained('artists')->cascadeOnDelete();
            $table->string('title');
            $table->date('release_date')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->enum('type', ['ALBUM', 'SINGLE', 'EP'])->index();
            $table->unsignedInteger('total_tracks')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('albums');
    }
};