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
        Schema::create('followers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_user_id');
            $table->string('username');
            $table->string('display_name');
            $table->string('avatar_url')->nullable();
            $table->timestamp('followed_at')->nullable();
            // Set by the chat bot; used by the active-viewer priority queue after v1 (#29).
            $table->timestamp('last_active_at')->nullable();
            // When a sync last saw this follower; anyone missing from a sync has unfollowed.
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'provider_user_id']);
            $table->index(['user_id', 'followed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('followers');
    }
};
