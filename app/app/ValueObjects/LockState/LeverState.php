<?php

declare(strict_types=1);

namespace App\ValueObjects\LockState;

class LeverState
{
    public function __construct(public readonly int $number, public readonly int $position)
    {
    }
}
