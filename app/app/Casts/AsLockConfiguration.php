<?php

namespace App\Casts;

use App\Enums\Direction;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AsLockConfiguration implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?LockConfiguration
    {
        if ($value === null) {
            return null;
        }

        $leversArr = json_decode($value, true);
        $levers = [];
        foreach ($leversArr as $leverArr) {
            $affects = [];
            foreach ($leverArr['affects'] as $affectArr) {
                $affects[] = new LeverAffect($affectArr['number'], Direction::from($affectArr['direction']));
            }
            $levers[] = new LeverConfiguration($leverArr['number'], $affects);
        }
        return new LockConfiguration($levers);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        if (!$value instanceof LockConfiguration) {
            throw new InvalidArgumentException('The given value is not an LockConfiguration instance.');
        }
        $leversArr = [];
        foreach ($value->levers() as $lever) {
            $leversArr[] = [
                'number' => $lever->number(),
                'affects' => array_map(
                    fn(LeverAffect $affect) => [
                        'number' => $affect->number(),
                        'direction' => $affect->direction()->value,
                    ],
                    $lever->affects(),
                ),
            ];
        }
        return json_encode($leversArr);
    }
}
