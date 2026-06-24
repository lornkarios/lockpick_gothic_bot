<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

use App\Enums\Direction;

class LeverAffect
{
    public function __construct(private int $number, private Direction $direction)
    {
    }

    public function number(): int
    {
        return $this->number;
    }

    public function direction(): Direction
    {
        return $this->direction;
    }
}
