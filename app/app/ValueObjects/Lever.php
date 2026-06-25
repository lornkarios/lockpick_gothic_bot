<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Direction;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockState\LeverState;
use Exception;

class Lever
{
    public function __construct(private LeverConfiguration $config, private LeverState $state, private Levers $levers)
    {
    }

    public function up(bool $withAffected = true): void
    {
        if (!$this->canUp($withAffected)) {
            throw new Exception('Lever can\'t be up');
        }
        $this->state->up();
        if (!$withAffected) {
            return;
        }
        foreach ($this->levers->affected($this->number(), Direction::TOGETHER) as $affected) {
            $affected->up(false);
        }
        foreach ($this->levers->affected($this->number(), Direction::SEPARATE) as $affected) {
            $affected->down(false);
        }
    }

    public function down(bool $withAffected = true): void
    {
        if (!$this->canDown($withAffected)) {
            throw new Exception('Lever can\'t be down');
        }
        $this->state()->down();
        if (!$withAffected) {
            return;
        }
        foreach ($this->levers->affected($this->number(), Direction::TOGETHER) as $affected) {
            $affected->down(false);
        }
        foreach ($this->levers->affected($this->number(), Direction::SEPARATE) as $affected) {
            $affected->up(false);
        }
    }

    public function canUp(bool $withAffected = true): bool
    {
        $canUp = $this->state->canUp();
        if (!$withAffected) {
            return $canUp;
        }
        foreach ($this->levers->affected($this->number(), Direction::TOGETHER) as $affected) {
            $canUp = $canUp && $affected->canUp(false);
        }
        foreach ($this->levers->affected($this->number(), Direction::SEPARATE) as $affected) {
            $canUp = $canUp && $affected->canDown(false);
        }
        return $canUp;
    }

    public function canDown(bool $withAffected = true): bool
    {
        $canDown = $this->state->canDown();
        if (!$withAffected) {
            return $canDown;
        }
        foreach ($this->levers->affected($this->number(), Direction::TOGETHER) as $affected) {
            $canDown = $canDown && $affected->canDown(false);
        }
        foreach ($this->levers->affected($this->number(), Direction::SEPARATE) as $affected) {
            $canDown = $canDown && $affected->canUp(false);
        }
        return $canDown;
    }

    public function number(): int
    {
        return $this->state->number();
    }

    public function state(): LeverState
    {
        return $this->state;
    }

    public function config(): LeverConfiguration
    {
        return $this->config;
    }
}
