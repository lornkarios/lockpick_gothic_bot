<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Direction;
use App\Models\Lockpick;
use App\Service\UnlockStates\NeedConfigurationHandler;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Models\TelegraphChat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class NeedConfigurationHandlerTest extends TestCase
{
    private NeedConfigurationHandler $handler;

    protected function setUp(): void
    {
        $chat = $this->createMock(TelegraphChat::class);
        $message = $this->createMock(Message::class);
        $lockpick = $this->createMock(Lockpick::class);

        $this->handler = new NeedConfigurationHandler($message, $lockpick);
    }

    #[DataProvider('validConfigProvider')]
    public function test_valid_config(string $config): void
    {
        $this->assertFalse($this->callIsConfigInvalid($config));
    }

    #[DataProvider('invalidConfigProvider')]
    public function test_invalid_config(string $config): void
    {
        $this->assertTrue($this->callIsConfigInvalid($config));
    }

    public function test_make_config_parses_correctly(): void
    {
        $config = $this->callMakeConfig('3:[2+ 3+,1+,2+]');

        $levers = $config->levers();
        $this->assertCount(3, $levers);

        $this->assertSame(1, $levers[0]->number());
        $this->assertCount(2, $levers[0]->affects());
        $this->assertEquals(new LeverAffect(2, Direction::TOGETHER), $levers[0]->affects()[0]);
        $this->assertEquals(new LeverAffect(3, Direction::TOGETHER), $levers[0]->affects()[1]);

        $this->assertSame(2, $levers[1]->number());
        $this->assertCount(1, $levers[1]->affects());
        $this->assertEquals(new LeverAffect(1, Direction::TOGETHER), $levers[1]->affects()[0]);

        $this->assertSame(3, $levers[2]->number());
        $this->assertCount(1, $levers[2]->affects());
        $this->assertEquals(new LeverAffect(2, Direction::TOGETHER), $levers[2]->affects()[0]);
    }

    public function test_make_config_empty_group(): void
    {
        $config = $this->callMakeConfig('3:[2+, ,1+]');

        $levers = $config->levers();
        $this->assertCount(3, $levers);
        $this->assertCount(1, $levers[0]->affects());
        $this->assertCount(0, $levers[1]->affects());
        $this->assertCount(1, $levers[2]->affects());
        $this->assertEquals(new LeverAffect(2, Direction::TOGETHER), $levers[0]->affects()[0]);
        $this->assertEquals(new LeverAffect(1, Direction::TOGETHER), $levers[2]->affects()[0]);
    }

    public static function validConfigProvider(): array
    {
        return [
            'one lever no affects' => ['1:[]'],
            'example from description' => ['5:[3+ 4-,1+,2+ 4-,5+,1-]'],
            'all affect others' => ['3:[2+,3+,1+]'],
            'mixed with others only' => ['4:[2+ 3-,1-,4+,2+ 1-]'],
            'no affects for some levers' => ['3:[2+, ,1+]'],
            'duplicate affect across different groups' => ['3:[2+,3+,2+]'],
        ];
    }

    public static function invalidConfigProvider(): array
    {
        return [
            'empty string' => [''],
            'no brackets' => ['3:abc'],
            'no colon' => ['3[1+,2+,3+]'],
            'wrong lever count too few' => ['3:[1+,2+]'],
            'wrong lever count too many' => ['3:[1+,2+,3+,1+]'],
            'affect out of range above' => ['3:[1+,4+,3+]'],
            'affect out of range zero' => ['3:[0+,2+,3+]'],
            'duplicate affect in same group' => ['3:[2+ 2+,3+,1+]'],
            'self affect' => ['1:[1+]'],
            'self affect in multi' => ['3:[1+,2+,3+]'],
            'self affect mixed' => ['3:[2+ 3-,1-,2+,1+]'],
            'invalid sign' => ['3:[2*,3+,1+]'],
            'missing sign' => ['3:[2,3+,1+]'],
            'no number before sign' => ['3:[+1,2+,3+]'],
            'letters instead of number' => ['3:[a+,2+,3+]'],
            'extra chars after bracket' => ['3:[1+,2+,3+]x'],
            'missing opening bracket' => ['3:1+,2+,3+]'],
            'missing closing bracket' => ['3:[1+,2+,3+'],
            'count of parts and config mismatch' => ['3:[1+,2+]'],
        ];
    }

    private function callIsConfigInvalid(string $config): bool
    {
        $method = new ReflectionMethod(NeedConfigurationHandler::class, 'isConfigInvalid');

        return $method->invoke($this->handler, $config);
    }

    private function callMakeConfig(string $config): LockConfiguration
    {
        $method = new ReflectionMethod(NeedConfigurationHandler::class, 'makeConfig');

        return $method->invoke($this->handler, $config);
    }
}
