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
        $this->assertHistorySequence($lockpick, [
            ['state' => [4, 4, 4]],
        ]);
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
        $this->assertHistorySequence($lockpick, [
            ['state' => [3, 4, 4]],
            ['state' => [4, 5, 4], 'is_up' => false, 'lever_number' => 1],
            ['state' => [4, 4, 4], 'is_up' => true, 'lever_number' => 2],
        ]);
    }

    public function test_handle_difficult_lock_success(): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::factory()->create([
            'lock_configuration' => new LockConfiguration([
                new LeverConfiguration(
                    1,
                    [
                        new LeverAffect(2, Direction::SEPARATE),
                        new LeverAffect(3, Direction::SEPARATE),
                        new LeverAffect(5, Direction::SEPARATE),
                    ]
                ),
                new LeverConfiguration(
                    2,
                    [
                        new LeverAffect(4, Direction::TOGETHER),
                        new LeverAffect(6, Direction::TOGETHER),
                    ]
                ),
                new LeverConfiguration(
                    3,
                    [
                        new LeverAffect(2, Direction::TOGETHER),
                        new LeverAffect(6, Direction::SEPARATE),
                    ]
                ),
                new LeverConfiguration(4, []),
                new LeverConfiguration(5, [new LeverAffect(1, Direction::SEPARATE)]),
                new LeverConfiguration(
                    6,
                    [
                        new LeverAffect(3, Direction::TOGETHER),
                        new LeverAffect(5, Direction::TOGETHER),
                    ]
                ),
            ]),
            'lock_state' => new LockState([
                new LeverState(1, 1),
                new LeverState(2, 2),
                new LeverState(3, 7),
                new LeverState(4, 7),
                new LeverState(5, 6),
                new LeverState(6, 5),
            ]),
        ]);

        $handler = new UnlockHandler($lockpick);
        $handler->handle();

        $lockpick->refresh();
        $this->assertSame(Status::UNLOCKED, $lockpick->status->name);
    }

    public function test_handle_impossible_lock(): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::factory()->create([
            'lock_configuration' => new LockConfiguration([
                new LeverConfiguration(1, [new LeverAffect(2, Direction::TOGETHER)]),
                new LeverConfiguration(2, [new LeverAffect(1, Direction::TOGETHER)]),
                new LeverConfiguration(3, []),
            ]),
            'lock_state' => new LockState([
                new LeverState(1, 7),
                new LeverState(2, 6),
                new LeverState(3, 2),
            ]),
        ]);

        $handler = new UnlockHandler($lockpick);
        $handler->handle();

        $lockpick->refresh();
        $this->assertSame(Status::NOT_UNLOCKABLE, $lockpick->status->name);
    }

    private function assertHistorySequence(Lockpick $lockpick, array $expectedSequence): void
    {
        $histories = LockpickHistory::query()->where('lockpick_id', $lockpick->id)->orderBy('id')->get();
        $this->assertCount(count($expectedSequence), $histories);

        foreach ($expectedSequence as $i => $expected) {
            $this->assertSame($expected['state'], $histories[$i]->lock_state->toArray());
            $this->assertSame($expected['is_up'] ?? null, $histories[$i]->is_up);
            $this->assertSame($expected['lever_number'] ?? null, $histories[$i]->lever_number);
        }
    }
}
