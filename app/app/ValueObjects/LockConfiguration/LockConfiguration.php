<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

use Exception;

class LockConfiguration
{
    /**
     * @param LeverConfiguration[] $levers
     */
    public function __construct(private array $levers)
    {
    }


    public function lever(int $number): LeverConfiguration
    {
        foreach ($this->levers as $lever) {
            if ($lever->number() === $number) {
                return $lever;
            }
        }
        throw new Exception('Lever not found');
    }

    public function levers(): array
    {
        return $this->levers;
    }
}
