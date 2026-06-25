<?php

declare(strict_types=1);

namespace App\ValueObjects\LockState;

use Exception;

class LockState
{
    /**
     * @param LeverState[] $levers
     */
    public function __construct(private array $levers)
    {
    }

    public function levers(): array
    {
        return $this->levers;
    }

    public function lever(int $number): LeverState
    {
        foreach ($this->levers as $lever) {
            if ($lever->number() === $number) {
                return $lever;
            }
        }
        throw new Exception('Lever not found');
    }

    public function toArray(): array
    {
        $arr = [];
        foreach ($this->levers as $state) {
            $arr[$state->number() - 1] = $state->position();
        }
        return $arr;
    }

    public function setFromArray(array $state): void
    {
        $levers = [];
        foreach ($state as $index => $position) {
            $number = $index + 1;
            $levers[] = new LeverState($number, $position);
        }
        $this->levers = $levers;
    }
}
