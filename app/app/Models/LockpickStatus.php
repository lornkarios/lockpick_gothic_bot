<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Status $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereName($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereUpdatedAt($value)
 */
class LockpickStatus extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'name' => Status::class,
        ];
    }

    public static function firstByName(Status $name): self
    {
        return self::query()->where('name', $name)->first();
    }
}
