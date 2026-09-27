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
        // Streamers only ever log in with Twitch, so there are no passwords or emails.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('twitch_id')->unique();
            $table->string('twitch_login');
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->text('twitch_access_token')->nullable();
            $table->text('twitch_refresh_token')->nullable();
            $table->timestamp('twitch_token_expires_at')->nullable();
            $table->json('twitch_scopes')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
