<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

use App\Enums\Direction;

class LeverAffect
{
    public function __construct(public readonly int $number, public readonly Direction $direction)
    {
    }
}
