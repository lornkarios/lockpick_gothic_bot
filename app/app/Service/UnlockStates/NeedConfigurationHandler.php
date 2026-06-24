<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use DefStudio\Telegraph\DTO\Message;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Bus\Dispatchable;

class NeedConfigurationHandler
{
    use Dispatchable;

    public function __construct(private TelegraphChat $chat, private Message $message, private Lockpick $lockpick)
    {
    }

    public function handle(TelegraphChat $chat, Message $message, Lockpick $lockpick): void
    {
        $invalid = $this->isConfigInvalid($configTxt = $message->text());
        if ($invalid) {
            $chat->message(__('telegram_bot.invalid_config') . __('telegram_bot.config_rule'))->send();
        }
        $lockpick->lock_configuration = $this->makeConfig($configTxt);
        $lockpick->status_id = LockpickStatus::firstByName(Status::CONFIGURATION)->id;
        $lockpick->save();
        $chat->message(__('telegram_bot.config_valid') . __('telegram_bot.state_rule'))->send();
    }

    private function isConfigInvalid(string $configuration): bool
    {
    }

    private function makeConfig(string $configuration):LockConfiguration
    {
    }
}
