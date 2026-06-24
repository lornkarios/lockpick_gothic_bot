<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\Direction;
use App\ValueObjects\LockConfiguration\LeverAffect;
use Exception;
use Illuminate\Support\Collection;

/**
 * @property Lever[] $items
 */
class Levers extends Collection
{
    /**
     * @return Collection<Lever>
     */
    public function affected(int $number, Direction $direction): Collection
    {
        $lever = $this->lever($number);
        $affectNumbers = array_map(
            fn(LeverAffect $affect) => $affect->number(),
            array_filter(
                $lever->config()->affects(),
                fn(LeverAffect $affect) => $affect->direction() === $direction,
            ),
        );
        return $this->filter(fn(Lever $lever) => in_array($lever->number(), $affectNumbers));
    }

    public function lever(int $number): Lever
    {
        foreach ($this->items as $lever) {
            if ($lever->number() === $number) {
                return $lever;
            }
        }
        throw new Exception('Lever not found');
    }

}
