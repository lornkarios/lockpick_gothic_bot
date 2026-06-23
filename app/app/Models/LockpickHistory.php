<?php

namespace App\Models;

use App\Casts\AsLockState;
use App\ValueObjects\LockState\LockState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property integer $id
 * @property integer $lockpick_id
 * @property-read Lockpick $lockpick
 * @property LockState $lock_state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 */
class LockpickHistory extends Model
{
    protected function casts(): array
    {
        return [
            'lock_state' => AsLockState::class,
        ];
    }

    public function lockpick(): BelongsTo
    {
        return $this->belongsTo(Lockpick::class, 'lockpick_id');
    }
}
