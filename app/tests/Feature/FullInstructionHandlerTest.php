<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Direction;
use App\Models\Lockpick;
use App\Service\UnlockStates\FullInstructionHandler;
use App\Service\UnlockStates\UnlockHandler;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FullInstructionHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function test_generates_instructions_for_solved_lock(): void
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

        $handler = new FullInstructionHandler($lockpick);
        $handler->handle();

        $requests = Http::recorded();
        $this->assertCount(2, $requests);

        $body = json_decode($requests[1][0]->body(), true);
        $this->assertStringContainsString('Lever 2 up', $body['text']);
        $this->assertStringContainsString('Lever 1 down', $body['text']);
    }
}
