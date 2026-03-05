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
        Schema::table('ai_playlist_usages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique('ai_playlist_usages_user_id_used_date_unique');
            $table->index(['user_id', 'used_date'], 'ai_playlist_usages_user_id_used_date_index');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_playlist_usages', function (Blueprint $table) {
            $table->dropIndex('ai_playlist_usages_user_id_used_date_index');
            $table->unique(['user_id', 'used_date'], 'ai_playlist_usages_user_id_used_date_unique');
        });
    }
};
