<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->text('moderation_note')->nullable()->after('status');
        });

        // Expand enum to include DRAFT and TAKEDOWN
        DB::statement("ALTER TABLE songs MODIFY COLUMN status ENUM('DRAFT','PENDING','APPROVED','REJECTED','TAKEDOWN') DEFAULT 'APPROVED'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE songs MODIFY COLUMN status ENUM('PENDING','APPROVED','REJECTED') DEFAULT 'APPROVED'");

        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('moderation_note');
        });
    }
};
