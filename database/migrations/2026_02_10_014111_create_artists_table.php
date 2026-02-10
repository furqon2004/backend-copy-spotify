<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
       Schema::create('artists', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('bio')->nullable();
    $table->string('avatar_url')->nullable();
    $table->unsignedBigInteger('monthly_listeners')->default(0);
    $table->boolean('is_verified')->default(false);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};