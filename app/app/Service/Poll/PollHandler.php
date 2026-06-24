<?php

declare(strict_types=1);

namespace App\Service\Poll;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use DefStudio\Telegraph\DTO\Message;
use App\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Exception;
use Illuminate\Support\Facades\Log;


class PollHandler
{
    public function __construct()
    {
    }

    public function handle(): void
    {
        $bot = TelegraphBot::query()->first();

        if (!$bot) {
            throw new Exception('Bot not found');
        }

        $updates = $bot->updates(offset: $bot->offset + 1);

        foreach ($updates as $update) {
            if (!is_null($update->message()?->text()) && !is_null($update->message()?->chat())) {
                $this->handleByMessage($bot, $update->message());
            }
            $bot->update(['offset' => $update->id()]);
            Log::info('Update handled', ['update' => $update->toArray()]);
        }
        sleep(5);
    }

    private function handleByMessage(TelegraphBot $bot, Message $message): void
    {
        /** @var TelegraphChat $chat */
        $chat = $bot->chats()->firstOrCreate(['chat_id' => $message->chat()->id()]);
        /** @var Lockpick|null $lockpick */
        $lockpick = Lockpick::query()->where(['chat_id' => $chat->id])->first();

        $status = $lockpick?->status->name;
        match (true) {
            $message->text() === '/start' => $this->start($chat, $message),
            $status === Status::START => $this->needConfiguration($chat, $message, $lockpick),
            $status === Status::CONFIGURATION => $this->needState($chat, $message, $lockpick),
        };
    }

    private function start(TelegraphChat $chat, Message $message): void
    {
        /** @var Lockpick $lockpick */
        $lockpick = Lockpick::query()->updateOrCreate(
            ['chat_id' => $chat->id],
            [
                'status_id' => LockpickStatus::firstByName(Status::START)->id,
                'lock_levers_count' => 0,
                'lock_configuration' => null,
                'lock_state' => null,
                'lockpick_history_id' => null,
            ],
        );
        $lockpick->history()->delete();
        $chat->message(__('telegram_bot.welcome'))->send();
    }

    private function needConfiguration(TelegraphChat $chat, Message $message, Lockpick $lockpick): void
    {
        //TODO validate config
        if ($invalid) {
            $chat->message(__('telegram_bot.invalid_config'))->send();
        }
        //TODO create config from message

        $lockpick->lock_configuration = $lockConfig;
        $lockpick->status_id = LockpickStatus::firstByName(Status::CONFIGURATION)->id;
        $lockpick->save();
        $chat->message(__('telegram_bot.config_valid'))->send();
    }

    private function needState(TelegraphChat $chat, Message $message, Lockpick $lockpick): void
    {
        //TODO validate state
        if ($invalid) {
            $chat->message(__('telegram_bot.invalid_state'))->send();
        }
        //TODO create state from message

        $lockpick->lock_configuration = $state;
        $lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKING)->id;
        $lockpick->save();
        $chat->message(__('telegram_bot.state_valid'))->send();
        //TODO unlocking lock
    }
}
