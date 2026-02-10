<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('songs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('artist_id')->constrained('artists')->onDelete('cascade');
    $table->foreignUuid('album_id')->nullable()->constrained('albums')->onDelete('set null');
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('cover_url');
    $table->string('file_path');
    $table->bigInteger('file_size');
    $table->integer('duration_seconds');
    $table->unsignedBigInteger('stream_count')->default(0);
    $table->boolean('is_explicit')->default(false);
    $table->timestamps();

    $table->index(['artist_id', 'stream_count']);
});
    }

    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};