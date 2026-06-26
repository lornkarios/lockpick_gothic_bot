<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\ValueObjects\LockState\LeverState;

class Lock
{
    public function __construct(private array $state, private array $config)
    {
    }

    public function up(int $number): void
    {
        $this->move($number, true);
    }

    public function down(int $number): void
    {
        $this->move($number, false);
    }

    private function move(int $number, bool $isUp): void
    {
        $this->moveState($number, $isUp);
        $affected = $this->config[$number - 1] ?? [];
        foreach ($affected as $index => $value) {
            $affectedNum = $index + 1;
            if ($value) {
                //together
                $this->moveState($affectedNum, $isUp);
            } else {
                //separate
                $this->moveState($affectedNum, !$isUp);
            }
        }
    }

    private function moveState(int $number, bool $isUp): void
    {
        if ($isUp) {
            $this->state[$number - 1] = $this->state[$number - 1] - 1;
        } else {
            $this->state[$number - 1] = $this->state[$number - 1] + 1;
        }
    }

    public function canUp(int $number): bool
    {
        return $this->canMove($number, true);
    }

    public function canDown(int $number): bool
    {
        return $this->canMove($number, false);
    }

    private function canMove(int $number, bool $isUp): bool
    {
        $canMove = $this->canMoveState($number, $isUp);
        $affected = $this->config[$number - 1] ?? [];
        foreach ($affected as $index => $value) {
            $affectedNum = $index + 1;
            if ($value) {
                //together
                $canMove = $canMove && $this->canMoveState($affectedNum, $isUp);
            } else {
                //separate
                $canMove = $canMove && $this->canMoveState($affectedNum, !$isUp);
            }
        }
        return $canMove;
    }

    private function canMoveState(int $number, bool $isUp): bool
    {
        if ($isUp) {
            $canMove = $this->state[$number - 1] - 1 >= LeverState::MIN_POSITION;
        } else {
            $canMove = $this->state[$number - 1] + 1 <= LeverState::MAX_POSITION;
        }
        return $canMove;
    }

    public function stateToArray(): array
    {
        return $this->state;
    }

    public function leversCount(): int
    {
        return count($this->state);
    }

    public function stateFromArray(array $stateArr): void
    {
        $this->state = $stateArr;
    }
}
