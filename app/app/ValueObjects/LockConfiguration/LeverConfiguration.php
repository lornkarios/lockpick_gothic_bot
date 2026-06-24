<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

class LeverConfiguration
{
    /**
     * @param LeverAffect[] $affects
     */
    public function __construct(private int $number, private array $affects)
    {
    }

    public function number(): int
    {
        return $this->number;
    }

    public function affects(): array
    {
        return $this->affects;
    }
}
