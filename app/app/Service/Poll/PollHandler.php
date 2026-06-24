<?php

declare(strict_types=1);

namespace App\Service\Poll;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Service\UnlockStates\NeedConfigurationHandler;
use App\Service\UnlockStates\NeedStateHandler;
use App\Service\UnlockStates\StartHandler;
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
            $message->text() === '/start' => StartHandler::dispatch($chat),
            $status === Status::START => NeedConfigurationHandler::dispatch($chat, $message, $lockpick),
            $status === Status::CONFIGURATION => NeedStateHandler::dispatch($chat, $message, $lockpick),
        };
    }
}
