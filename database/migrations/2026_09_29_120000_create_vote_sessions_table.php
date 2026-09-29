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
        Schema::create('vote_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('wave');
            // The upgrades on offer, numbered from 1: [{number, id, name, description}].
            $table->json('options');
            $table->timestamp('closes_at');
            $table->timestamp('closed_at')->nullable();
            // The winning upgrade's id; null if the session was replaced before closing.
            $table->string('winner')->nullable();
            $table->string('closed_by')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'closed_at']);
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_session_id')->constrained()->cascadeOnDelete();
            // The viewer's id on the chat platform.
            $table->string('voter_id');
            $table->unsignedTinyInteger('choice');
            $table->timestamps();

            $table->unique(['vote_session_id', 'voter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
        Schema::dropIfExists('vote_sessions');
    }
};
