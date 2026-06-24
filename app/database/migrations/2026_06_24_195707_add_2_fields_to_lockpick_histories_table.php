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
        Schema::table('lockpick_histories', function (Blueprint $table) {
            $table->boolean('is_up')->nullable();
            $table->integer('lever_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lockpick_histories', function (Blueprint $table) {
            $table->dropColumn('is_up');
            $table->dropColumn('lever_number');
        });
    }
};
