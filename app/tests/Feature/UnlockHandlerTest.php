<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Direction;
use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickHistory;
use App\Service\UnlockStates\UnlockHandler;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UnlockHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_handle_success_when_already_unlocked(): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::factory()->create([
            'lock_state' => new LockState([
                new LeverState(1, 4),
                new LeverState(2, 4),
                new LeverState(3, 4),
            ]),
        ]);

        $handler = new UnlockHandler($lockpick);
        $handler->handle();

        $lockpick->refresh();
        $this->assertSame(Status::UNLOCKED, $lockpick->status->name);

        $histories = LockpickHistory::query()->where('lockpick_id', $lockpick->id)->orderBy('id')->get();
        $this->assertCount(1, $histories);
        $this->assertSame(4, $histories[0]->lock_state->lever(1)->position());
        $this->assertSame(4, $histories[0]->lock_state->lever(2)->position());
        $this->assertSame(4, $histories[0]->lock_state->lever(3)->position());
    }

    public function test_handle_finds_solution(): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::factory()->create([
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
        ]);

        $handler = new UnlockHandler($lockpick);
        $handler->handle();

        $lockpick->refresh();
        $this->assertSame(Status::UNLOCKED, $lockpick->status->name);
        $this->assertDatabaseCount('lockpick_histories', 3);
        $this->assertDatabaseHas('lockpick_histories', [
            'lockpick_id' => $lockpick->id,
            'lock_state' => json_encode([3, 4, 4]),
            'is_up' => null,
            'lever_number' => null,
        ]);
        $this->assertDatabaseHas('lockpick_histories', [
            'lockpick_id' => $lockpick->id,
            'lock_state' => json_encode([3, 3, 4]),
            'is_up' => true,
            'lever_number' => 2,
        ]);
        $this->assertDatabaseHas('lockpick_histories', [
            'lockpick_id' => $lockpick->id,
            'lock_state' => json_encode([4, 4, 4]),
            'is_up' => false,
            'lever_number' => 1,
        ]);
    }

    public function test_handle_impossible_lock(): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::factory()->create([
            'lock_configuration' => new LockConfiguration([
                new LeverConfiguration(1, [new LeverAffect(2, Direction::TOGETHER)]),
                new LeverConfiguration(2, []),
                new LeverConfiguration(3, []),
            ]),
            'lock_state' => new LockState([
                new LeverState(1, 2),
                new LeverState(2, 2),
                new LeverState(3, 2),
            ]),
        ]);

        $handler = new UnlockHandler($lockpick);
        $handler->handle();

        $lockpick->refresh();
        $this->assertSame(Status::NOT_UNLOCKABLE, $lockpick->status->name);
    }
}
