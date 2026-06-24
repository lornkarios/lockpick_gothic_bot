<?php

namespace App\Casts;

use App\ValueObjects\Lock;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AsLock implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Lock
    {
        return new Lock($attributes['lock_configuration'], $attributes['lock_state']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof Lock) {
            throw new InvalidArgumentException('The given value is not an Lock instance.');
        }

        return [
            'lock_configuration' => $value->config(),
            'lock_state' => $value->state(),
        ];
    }
}
