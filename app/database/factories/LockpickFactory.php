<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Direction;
use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Factories\Factory;

class LockpickFactory extends Factory
{
    protected $model = Lockpick::class;

    public function definition(): array
    {
        return [
            'chat_id' => TelegraphChat::factory(),
            'status_id' => LockpickStatus::firstByName(Status::UNLOCKING)->id,
            'lock_levers_count' => 3,
            'lock_configuration' => new LockConfiguration([
                new LeverConfiguration(1, [new LeverAffect(2, Direction::TOGETHER)]),
                new LeverConfiguration(2, []),
                new LeverConfiguration(3, []),
            ]),
            'lock_state' => new LockState([
                new LeverState(1, 3),
                new LeverState(2, 4),
                new LeverState(3, 4),
            ]),
        ];
    }
}
