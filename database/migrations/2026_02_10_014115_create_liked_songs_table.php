<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('liked_songs', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('song_id')->constrained('songs')->cascadeOnDelete();
            $table->timestamp('liked_at')->useCurrent()->index();
            $table->primary(['user_id', 'song_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liked_songs');
    }
};