<?php

declare(strict_types=1);

namespace App\ValueObjects\LockState;

use Exception;

class LeverState
{
    public const MIN_POSITION = 1;
    public const MAX_POSITION = 7;

    public const UNLOCK_POSITION = 4;

    public function __construct(private int $number, private int $position)
    {
    }

    public function canUp(): bool
    {
        return $this->position - 1 > self::MIN_POSITION;
    }

    public function canDown(): bool
    {
        return $this->position + 1 < self::MAX_POSITION;
    }

    public function up(): void
    {
        if (!$this->canUp()) {
            throw new Exception('Lever can\'t be up');
        }
        $this->position = $this->position - 1;
    }

    public function down(): void
    {
        if (!$this->canDown()) {
            throw new Exception('Lever can\'t be down');
        }
        $this->position = $this->position + 1;
    }

    public function number(): int
    {
        return $this->number;
    }

    public function position(): int
    {
        return $this->position;
    }
}
