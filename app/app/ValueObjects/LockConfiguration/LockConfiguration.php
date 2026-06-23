<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

class LockConfiguration
{
    /**
     * @param LeverConfiguration[] $levers
     */
    public function __construct(public readonly array $levers)
    {
    }
}
