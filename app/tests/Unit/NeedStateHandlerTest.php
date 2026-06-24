<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Direction;
use App\Models\Lockpick;
use App\Service\UnlockStates\NeedStateHandler;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Models\TelegraphChat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NeedStateHandlerTest extends TestCase
{
    private NeedStateHandler $handler;

    protected function setUp(): void
    {
        $this->handler = $this->createHandler(3);
    }

    private function createHandler(int $leversCount): NeedStateHandler
    {
        $chat = $this->createMock(TelegraphChat::class);
        $message = $this->createMock(Message::class);

        $levers = [];
        for ($i = 1; $i <= $leversCount; $i++) {
            $levers[] = new LeverConfiguration($i, []);
        }

        $lockpick = new Lockpick();
        $lockpick->lock_configuration = new LockConfiguration($levers);

        return new NeedStateHandler($message, $lockpick);
    }

    #[DataProvider('validStateProvider')]
    public function test_valid_state(int $leversCount, string $state): void
    {
        $handler = $this->createHandler($leversCount);

        $this->assertFalse($this->callIsInvalid($handler, $state));
    }

    #[DataProvider('invalidStateProvider')]
    public function test_invalid_state(int $leversCount, string $state): void
    {
        $handler = $this->createHandler($leversCount);

        $this->assertTrue($this->callIsInvalid($handler, $state));
    }

    public function test_make_state_parses_correctly(): void
    {
        $state = $this->callMakeState($this->handler, '3:[5,1,3]');

        $levers = $state->levers();
        $this->assertCount(3, $levers);

        $this->assertSame(1, $levers[0]->number());
        $this->assertSame(5, $levers[0]->position());

        $this->assertSame(2, $levers[1]->number());
        $this->assertSame(1, $levers[1]->position());

        $this->assertSame(3, $levers[2]->number());
        $this->assertSame(3, $levers[2]->position());
    }

    public static function validStateProvider(): array
    {
        return [
            'one lever' => [1, '1:[3]'],
            'three levers' => [3, '3:[5,1,3]'],
            'five levers' => [5, '5:[1,1,3,5,2]'],
            'min value' => [2, '2:[1,7]'],
            'max value' => [2, '2:[7,1]'],
        ];
    }

    public static function invalidStateProvider(): array
    {
        return [
            'empty string' => [3, ''],
            'no brackets' => [3, '3:abc'],
            'no colon' => [3, '3[1,2,3]'],
            'wrong value count too few' => [3, '3:[1,2]'],
            'wrong value count too many' => [3, '3:[1,2,3,4]'],
            'non-numeric value' => [3, '3:[1,a,3]'],
            'empty between commas' => [3, '3:[1,,3]'],
            'negative number' => [3, '3:[1,-2,3]'],
            'value zero' => [3, '3:[1,0,3]'],
            'value above 7' => [3, '3:[1,8,3]'],
            'value far above' => [3, '3:[100,200,300]'],
            'count mismatch' => [3, '5:[1,2,3,4,5]'],
            'missing opening bracket' => [3, '3:1,2,3]'],
            'missing closing bracket' => [3, '3:[1,2,3'],
            'extra chars after bracket' => [3, '3:[1,2,3]x'],
        ];
    }

    private function callIsInvalid(NeedStateHandler $handler, string $state): bool
    {
        $method = new ReflectionMethod(NeedStateHandler::class, 'isInvalid');

        return $method->invoke($handler, $state);
    }

    private function callMakeState(NeedStateHandler $handler, string $state): LockState
    {
        $method = new ReflectionMethod(NeedStateHandler::class, 'makeState');

        return $method->invoke($handler, $state);
    }
}
