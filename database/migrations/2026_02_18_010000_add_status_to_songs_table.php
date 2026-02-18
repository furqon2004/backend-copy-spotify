<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('APPROVED')->after('is_explicit');
        });
    }

    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
