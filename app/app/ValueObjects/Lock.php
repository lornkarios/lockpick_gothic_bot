<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;

class Lock
{
    private Levers $levers;

    public function __construct(private readonly LockConfiguration $config, private readonly LockState $state)
    {
        $this->setLevers();
    }

    public function up(int $number): void
    {
        $this->lever($number)->up();
    }

    public function down(int $number): void
    {
        $this->lever($number)->down();
    }

    public function canUp(int $number): bool
    {
        return $this->lever($number)->canUp();
    }

    public function canDown(int $number): bool
    {
        return $this->lever($number)->canDown();
    }

    public function config(): LockConfiguration
    {
        return $this->config;
    }

    public function state(): LockState
    {
        return $this->state;
    }

    public function isUnlocked(): bool
    {
        $isUnlocked = true;
        foreach ($this->levers as $lever) {
            $isUnlocked = $isUnlocked && $lever->state()->position() === LeverState::UNLOCK_POSITION;
        }
        return $isUnlocked;
    }

    public function leversCount(): int
    {
        return count($this->state()->levers());
    }

    private function setLevers(): void
    {
        $this->levers = new Levers();
        foreach ($this->state()->levers() as $state) {
            $config = $this->config()->lever($state->number());
            $this->levers->add(new Lever($config, $state, $this->levers));
        }
    }

    private function lever(int $number): Lever
    {
        return $this->levers->lever($number);
    }
}
