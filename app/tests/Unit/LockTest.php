<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Direction;
use App\ValueObjects\Lock;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use Exception;
use PHPUnit\Framework\TestCase;

class LockTest extends TestCase
{
    private Lock $lock;

    protected function setUp(): void
    {
        $this->lock = $this->createLock();
    }

    public function test_up_moves_lever_and_affected_together(): void
    {
        $this->lock->up(2);

        $this->assertSame([4, 3, 3, 4], $this->lock->stateToArray());
    }

    public function test_down_moves_lever_and_affected_together(): void
    {
        $this->lock->down(2);

        $this->assertSame([4, 5, 5, 4], $this->lock->stateToArray());
    }

    public function test_up_moves_together_and_separate(): void
    {
        $this->lock->up(1);

        $this->assertSame([3, 3, 5, 4], $this->lock->stateToArray());
    }

    public function test_down_moves_together_and_separate(): void
    {
        $this->lock->down(1);

        $this->assertSame([5, 5, 3, 4], $this->lock->stateToArray());
    }

    public function test_can_up_returns_false_when_lever_at_min(): void
    {
        $lock = $this->createLock(p1: 1);

        $this->assertFalse($lock->canUp(1));
    }

    public function test_can_up_returns_false_when_affected_together_cannot_move(): void
    {
        $lock = $this->createLock(p1: 4, p2: 1);

        $this->assertFalse($lock->canUp(1));
    }

    public function test_can_up_returns_false_when_affected_separate_cannot_move(): void
    {
        $lock = $this->createLock(p1: 4, p3: 7);

        $this->assertFalse($lock->canUp(1));
    }

    public function test_can_down_returns_false_when_lever_at_max(): void
    {
        $lock = $this->createLock(p1: 7);

        $this->assertFalse($lock->canDown(1));
    }

    public function test_can_down_returns_false_when_affected_together_cannot_move(): void
    {
        $lock = $this->createLock(p1: 4, p2: 7);

        $this->assertFalse($lock->canDown(1));
    }

    public function test_can_down_returns_false_when_affected_separate_cannot_move(): void
    {
        $lock = $this->createLock(p1: 4, p3: 1);

        $this->assertFalse($lock->canDown(1));
    }

    /**
     * Config: 4:[2+ 3-,3+,2+,]
     *   Lever 1 -> affects 2 (together), 3 (separate)
     *   Lever 2 -> affects 3 (together)
     *   Lever 3 -> affects 2 (together)
     *   Lever 4 -> no affects
     */
    private function createLock(int $p1 = 4, int $p2 = 4, int $p3 = 4, int $p4 = 4): Lock
    {
        $config = new LockConfiguration([
            new LeverConfiguration(1, [
                new LeverAffect(2, Direction::TOGETHER),
                new LeverAffect(3, Direction::SEPARATE),
            ]),
            new LeverConfiguration(2, [new LeverAffect(3, Direction::TOGETHER)]),
            new LeverConfiguration(3, [new LeverAffect(2, Direction::TOGETHER)]),
            new LeverConfiguration(4, []),
        ]);

        $state = new LockState([
            new LeverState(1, $p1),
            new LeverState(2, $p2),
            new LeverState(3, $p3),
            new LeverState(4, $p4),
        ]);

        return new Lock($state->toArray(), $config->toArray());
    }
}
