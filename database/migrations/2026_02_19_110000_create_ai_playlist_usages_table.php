<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_playlist_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('prompt');
            $table->date('used_date');
            $table->timestamps();

            $table->unique(['user_id', 'used_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_playlist_usages');
    }
};
