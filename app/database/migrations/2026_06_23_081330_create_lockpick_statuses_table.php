<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lockpick_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        DB::table('lockpick_statuses')->insert($this->statuses());
    }

    private function statuses(): array
    {
        return [
            ['name' => 'Start'],
            ['name' => 'Configuration'],
            ['name' => 'Unlocking'],
            ['name' => 'Unlocked'],
            ['name' => 'StepByStepUnlocking'],
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lockpick_statuses');
    }
};
