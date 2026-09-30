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
        // Admins' changes to global balance. Settings not listed use their default from config/game.php.
        Schema::create('balance_overrides', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->double('value');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Can edit global game balance. Granted with `php artisan admin:grant`.
            $table->boolean('is_admin')->default(false)->after('voting_window_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::dropIfExists('balance_overrides');
    }
};
