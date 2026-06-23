<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

class LeverConfiguration
{
    /**
     * @param LeverAffect[] $affects
     */
    public function __construct(public readonly int $number, public readonly array $affects)
    {
    }
}
