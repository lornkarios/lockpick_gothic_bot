<?php

declare(strict_types=1);

namespace App\ValueObjects\LockState;

class LockState
{
    /**
     * @param LeverState[] $levers
     */
    public function __construct(public readonly array $levers)
    {
    }
}
