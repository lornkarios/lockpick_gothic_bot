<?php

declare(strict_types=1);

namespace App\ValueObjects\LockConfiguration;

use App\Enums\Direction;
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

    public function toArray(): array
    {
        $data = [];
        foreach ($this->levers as $lever) {
            $leverArr = [];
            foreach ($lever->affects() as $affect) {
                $leverArr[$affect->number() - 1] = $affect->direction() === Direction::TOGETHER;
            }
            $data[$lever->number() - 1] = $leverArr;
        }
        return $data;
    }
}
