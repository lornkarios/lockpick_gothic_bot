<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lockpicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->unique()->constrained('telegraph_chats')->cascadeOnDelete();
            $table->foreignId('status_id')->constrained('lockpick_statuses');
            $table->integer('lock_levers_count')->default(0);
            $table->json('lock_configuration')->nullable();
            $table->json('lock_state')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lockpicks');
    }
};
