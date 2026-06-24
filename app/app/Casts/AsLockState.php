<?php

namespace App\Casts;

use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AsLockState implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): LockState
    {
        $leversArr = json_decode($value, true);
        $levers = [];
        foreach ($leversArr as $leverArr) {
            $levers[] = new LeverState($leverArr['number'], $leverArr['position']);
        }
        return new LockState($levers);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }
        if (!$value instanceof LockState) {
            throw new InvalidArgumentException('The given value is not an LockState instance.');
        }
        $leversArr = [];
        foreach ($value->levers as $lever) {
            $leversArr[] = [
                'number' => $lever->number,
                'position' => $lever->position,
            ];
        }
        return json_encode($leversArr);
    }
}
