<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('album_id')->index()->constrained('albums')->cascadeOnDelete();
            $table->foreignUuid('artist_id')->index()->constrained('artists')->cascadeOnDelete();
            $table->string('title')->index();
            $table->unsignedInteger('duration_ms');
            $table->string('file_url');
            $table->unsignedInteger('track_number');
            $table->unsignedBigInteger('stream_count')->default(0)->index();
            $table->text('lyrics')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};