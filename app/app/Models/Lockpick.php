<?php

namespace App\Models;

use App\Casts\AsLockConfiguration;
use App\Casts\AsLockState;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use App\ValueObjects\LockState\LockState;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property integer $id
 * @property integer $chat_id
 * @property-read TelegraphChat $chat
 * @property integer $status_id
 * @property-read LockpickStatus $status
 * @property integer $lock_levers_count
 * @property LockConfiguration $lock_configuration
 * @property LockState $lock_state
 * @property integer $lockpick_history_id
 * @property-read LockpickHistory $historyStep
 * @property-read Collection<LockpickHistory> $history
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 */
class Lockpick extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'lock_configuration' => AsLockConfiguration::class,
            'lock_state' => AsLockState::class,
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(TelegraphChat::class, 'chat_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LockpickStatus::class, 'status_id');
    }

    public function historyStep(): BelongsTo
    {
        return $this->belongsTo(LockpickHistory::class, 'lockpick_history_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(LockpickHistory::class, 'lockpick_id');
    }
}
