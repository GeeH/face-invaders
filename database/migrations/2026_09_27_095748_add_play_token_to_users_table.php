<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The secret in the streamer's game URL (/play/{token}) that goes into OBS.
            $table->string('play_token', 64)->nullable()->unique()->after('display_name');
        });

        DB::table('users')->whereNull('play_token')->lazyById()->each(
            fn (object $user) => DB::table('users')->where('id', $user->id)->update(['play_token' => Str::random(40)]),
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['play_token']);
            $table->dropColumn('play_token');
        });
    }
};
