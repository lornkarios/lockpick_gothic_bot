<?php

declare(strict_types=1);

namespace App\Service\Poll;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Service\UnlockStates\FullInstructionHandler;
use App\Service\UnlockStates\NeedConfigurationHandler;
use App\Service\UnlockStates\NeedStateHandler;
use App\Service\UnlockStates\StartHandler;
use App\Service\UnlockStates\StepByStepHandler;
use DefStudio\Telegraph\DTO\CallbackQuery;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use App\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            if (!is_null($update->callbackQuery())) {
                $this->handleByCallback($bot, $update->callbackQuery());
            } elseif (!is_null($update->message()?->text()) && !is_null($update->message()?->chat())) {
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
        try {
            match (true) {
                $message->text() === '/start' => StartHandler::dispatch($chat),
                $status === Status::START => NeedConfigurationHandler::dispatch($message, $lockpick),
                $status === Status::CONFIGURATION => NeedStateHandler::dispatch($message, $lockpick),
                $status === Status::UNLOCKING =>
                $chat->message(__('telegram_bot.unlocking_in_progress'))->send(),
                $status === Status::UNLOCKED =>
                $chat->message(__('telegram_bot.already_unlocked'))
                    ->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                        Button::make(__('telegram_bot.step_by_step'))->action('step_by_step'),
                        Button::make(__('telegram_bot.full_instruction'))->action('full_instruction'),
                    ]))
                    ->send(),
                $status === Status::STEP_BY_STEP_UNLOCKING =>
                $chat->message(__('telegram_bot.step_reminder'))->send(),
            };
        } catch (Throwable $e) {
            Log::error('Message handling error: ' . $e->getMessage(), [
                'text' => $message->text(),
                'chat_id' => $message->chat()->id(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function handleByCallback(TelegraphBot $bot, CallbackQuery $callbackQuery): void
    {
        $action = $callbackQuery->data()->get('action');

        if (!$action) {
            return;
        }

        $message = $callbackQuery->message();

        if (!$message || !$message->chat()) {
            return;
        }

        try {
            $chatId = $message->chat()->id();
            /** @var TelegraphChat $chat */
            $chat = $bot->chats()->firstOrCreate(['chat_id' => $chatId]);
            /** @var Lockpick|null $lockpick */
            $lockpick = Lockpick::query()->where(['chat_id' => $chat->id])->first();

            if (!$lockpick) {
                return;
            }

            $status = $lockpick->status->name;

            if ($action === 'full_instruction' && $status === Status::UNLOCKED) {
                $bot->replyWebhook($callbackQuery->id(), '')->send();
                FullInstructionHandler::dispatch($lockpick);
                return;
            }

            if ($action === 'step_by_step' && $status === Status::UNLOCKED) {
                $bot->replyWebhook($callbackQuery->id(), '')->send();
                StepByStepHandler::dispatch($lockpick);
                return;
            }

            if ($action === 'next_step' && $status === Status::STEP_BY_STEP_UNLOCKING) {
                $bot->replyWebhook($callbackQuery->id(), '')->send();
                StepByStepHandler::dispatch($lockpick, true);
            }
        } catch (Throwable $e) {
            Log::error('Callback handling error: ' . $e->getMessage(), [
                'action' => $action,
                'chat_id' => $message->chat()->id() ?? null,
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
