<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Bus\Dispatchable;

class NeedStateHandler
{
    use Dispatchable;

    public function __construct(private TelegraphChat $chat, private Message $message, private Lockpick $lockpick)
    {
    }

    public function handle(TelegraphChat $chat, Message $message, Lockpick $lockpick): void
    {
        //TODO validate state
        if ($invalid) {
            $chat->message(__('telegram_bot.invalid_state'). __('telegram_bot.state_rule'))->send();
        }
        //TODO create state from message

        $lockpick->lock_configuration = $state;
        $lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKING)->id;
        $lockpick->save();
        $chat->message(__('telegram_bot.state_valid'))->send();
        //TODO unlocking lock
    }

}
