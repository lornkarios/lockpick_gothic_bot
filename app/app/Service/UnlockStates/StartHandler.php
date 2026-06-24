<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Bus\Dispatchable;

class StartHandler
{
    use Dispatchable;

    public function __construct(private TelegraphChat $chat)
    {
    }

    public function handle()
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::query()->updateOrCreate(
            ['chat_id' => $this->chat->id],
            [
                'status_id' => LockpickStatus::firstByName(Status::START)->id,
                'lock_levers_count' => 0,
                'lock_configuration' => null,
                'lock_state' => null,
                'lockpick_history_id' => null,
            ],
        );
        $lockpick->history()->delete();
        $this->chat->message(__('telegram_bot.welcome') . __('telegram_bot.config_rule'))->send();
    }
}
