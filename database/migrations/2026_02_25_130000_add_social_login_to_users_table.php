<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('password_hash');
            $table->string('provider_id')->nullable()->after('provider');

            // Make password_hash nullable for social login users
            $table->string('password_hash')->nullable()->change();

            // Unique constraint: same provider + provider_id combo
            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['provider', 'provider_id']);
            $table->dropColumn(['provider', 'provider_id']);

            // Revert password_hash to not nullable
            $table->string('password_hash')->nullable(false)->change();
        });
    }
};
