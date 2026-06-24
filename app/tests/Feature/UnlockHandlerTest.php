<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Direction;
use App\Enums\Status;
use App\Models\Lockpick;
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
        $this->assertSame(4, $lockpick->lock_state->lever(1)->position());
        $this->assertSame(4, $lockpick->lock_state->lever(2)->position());
        $this->assertSame(4, $lockpick->lock_state->lever(3)->position());
        $this->assertSame(Status::UNLOCKED, $lockpick->status->name);
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
