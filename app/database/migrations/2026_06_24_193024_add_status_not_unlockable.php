<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('lockpick_statuses')->insert($this->statuses());
    }

    private function statuses(): array
    {
        return [
            ['name' => 'NotUnlockable'],
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
