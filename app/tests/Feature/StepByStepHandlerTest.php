<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Direction;
use App\Enums\Status;
use App\Models\Lockpick;
use App\Service\UnlockStates\StepByStepHandler;
use App\Service\UnlockStates\UnlockHandler;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StepByStepHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_shows_current_state_and_next_action(): void
    {
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

        $unlockHandler = new UnlockHandler($lockpick);
        $unlockHandler->handle();

        $lockpick->refresh();

        $stepHandler = new StepByStepHandler($lockpick);
        $stepHandler->handle();

        $lockpick->refresh();

        $this->assertSame(Status::STEP_BY_STEP_UNLOCKING, $lockpick->status->name);
        $this->assertNotNull($lockpick->lockpick_history_id);

        $body = json_decode(Http::recorded()[1][0]->body(), true);
        $this->assertSame(implode("\n", [
            '- - -',
            '- - -',
            '0 - -',
            '- 0 0',
            '- - -',
            '- - -',
            '- - -',
            'Move lever 2 up',
        ]), $body['text']);

        $advanceHandler = new StepByStepHandler($lockpick, true);
        $advanceHandler->handle();

        $lockpick->refresh();

        $this->assertSame(Status::STEP_BY_STEP_UNLOCKING, $lockpick->status->name);

        $body = json_decode(Http::recorded()[2][0]->body(), true);
        $this->assertSame(implode("\n", [
            '- - -',
            '- - -',
            '0 0 -',
            '- - 0',
            '- - -',
            '- - -',
            '- - -',
            'Move lever 1 down',
        ]), $body['text']);

        $advanceHandler = new StepByStepHandler($lockpick, true);
        $advanceHandler->handle();

        $lockpick->refresh();

        $this->assertSame(Status::UNLOCKED, $lockpick->status->name);
        $this->assertNull($lockpick->lockpick_history_id);

        $body = json_decode(Http::recorded()[3][0]->body(), true);
        $this->assertSame(implode("\n", [
            '- - -',
            '- - -',
            '- - -',
            '0 0 0',
            '- - -',
            '- - -',
            '- - -',
            'All steps complete! Lock is unlocked.',
        ]), $body['text']);
    }
}
