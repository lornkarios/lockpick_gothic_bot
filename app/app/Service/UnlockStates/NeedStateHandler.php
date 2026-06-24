<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Foundation\Bus\Dispatchable;

class NeedStateHandler
{
    use Dispatchable;

    public function __construct(private Message $message, private Lockpick $lockpick)
    {
    }

    public function handle(): void
    {
        $lockpick = $this->lockpick;
        if ($this->isInvalid($this->message->text())) {
            $lockpick->chat->message(__('telegram_bot.invalid_state') . __('telegram_bot.state_rule'))->send();
            return;
        }
        $lockpick->lock_state = $this->makeState($this->message->text());
        $lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKING)->id;
        $lockpick->save();
        $lockpick->chat->message(__('telegram_bot.state_valid'))->send();
        UnlockHandler::dispatch($lockpick);
    }

    private function isInvalid(string $stateTxt): bool
    {
        if (!preg_match('/^(\d+):\[(.*)]$/', $stateTxt, $matches)) {
            return true;
        }

        $leversCount = (int) $matches[1];
        $values = explode(',', trim($matches[2]));

        if (count($values) !== $leversCount) {
            return true;
        }

        if ($leversCount !== count($this->lockpick->lock_configuration->levers())) {
            return true;
        }

        foreach ($values as $value) {
            $value = trim($value);
            if ($value === '') {
                return true;
            }
            if (!preg_match('/^\d+$/', $value)) {
                return true;
            }
            if ((int) $value < LeverState::MIN_POSITION || (int) $value > LeverState::MAX_POSITION) {
                return true;
            }
        }

        return false;
    }

    private function makeState(string $stateTxt): LockState
    {
        preg_match('/^(\d+):\[(.*)]$/', $stateTxt, $matches);
        $values = explode(',', trim($matches[2]));

        $levers = [];
        foreach ($values as $index => $value) {
            $levers[] = new LeverState(number: $index + 1, position: (int) trim($value));
        }

        return new LockState($levers);
    }
}
