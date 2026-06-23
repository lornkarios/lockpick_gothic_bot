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
        Schema::create('lockpick_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lockpick_id')->constrained('lockpicks');
            $table->json('lock_state');
            $table->timestamps();
        });
        Schema::table('lockpicks', function (Blueprint $table) {
            $table->foreignId('lockpick_history_id')->nullable()->constrained('lockpick_histories');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lockpicks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lockpick_history_id');
        });
        Schema::dropIfExists('lockpick_histories');
    }
};
