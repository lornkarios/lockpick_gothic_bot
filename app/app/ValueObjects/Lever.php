<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Direction;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockState\LeverState;
use Exception;

class Lever
{
    public function __construct(private LeverConfiguration $config, private LeverState $state, private Lock $lock)
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
        $this->moveAffected(Direction::TOGETHER, true);
        $this->moveAffected(Direction::SEPARATE, true);
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
        $this->moveAffected(Direction::TOGETHER, false);
        $this->moveAffected(Direction::SEPARATE, false);
    }

    public function canUp(bool $withAffected = true): bool
    {
        $canUp = $this->state->canUp();
        if (!$withAffected) {
            return $canUp;
        }
        $canUp = $canUp && $this->canMoveAffected(Direction::TOGETHER, true);
        return $canUp && $this->canMoveAffected(Direction::SEPARATE, true);
    }

    public function canDown(bool $withAffected = true): bool
    {
        $canDown = $this->state->canDown();
        if (!$withAffected) {
            return $canDown;
        }
        $canDown = $canDown && $this->canMoveAffected(Direction::TOGETHER, false);
        return $canDown && $this->canMoveAffected(Direction::SEPARATE, false);
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

    private function canMoveAffected(Direction $direction, bool $isUp): bool
    {
        $canMove = true;
        $affectedNumbers = $this->affectedNumbers($direction);
        foreach ($this->lock->levers() as $lever) {
            if (!in_array($lever->number(), $affectedNumbers)) {
                continue;
            }
            $canMoveItem = $direction === Direction::TOGETHER ?
                ($isUp ? $lever->canUp(false) : $lever->canDown(false)) :
                ($isUp ? $lever->canDown(false) : $lever->canUp(false));
            $canMove = $canMove && $canMoveItem;
        }
        return $canMove;
    }

    private function moveAffected(Direction $direction, bool $isUp): void
    {
        $affectedNumbers = $this->affectedNumbers($direction);
        foreach ($this->lock->levers() as $lever) {
            if (!in_array($lever->number(), $affectedNumbers)) {
                continue;
            }
            if ($direction === Direction::TOGETHER) {
                if ($isUp) {
                    $lever->up(false);
                } else {
                    $lever->down(false);
                }
            } else {
                if ($isUp) {
                    $lever->down(false);
                } else {
                    $lever->up(false);
                }
            }
        }
    }

    private function affectedNumbers(Direction $direction)
    {
        return array_map(
            fn(LeverAffect $affect) => $affect->number(),
            array_filter(
                $this->config()->affects(),
                fn(LeverAffect $affect) => $affect->direction() === $direction,
            ),
        );
    }
}
