<?php

namespace App\Casts;

use App\Models\Lockpick;
use App\ValueObjects\Lock;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class AsLock implements CastsAttributes
{
    /**
     * @param Lockpick $model
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): Lock
    {
        return new Lock($model->lock_state->toArray(), $model->lock_configuration->toArray());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        throw new RuntimeException('Method set() not implemented.');
    }
}
