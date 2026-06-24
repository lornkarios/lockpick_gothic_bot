<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Direction;
use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use App\ValueObjects\LockConfiguration\LeverAffect;
use App\ValueObjects\LockConfiguration\LeverConfiguration;
use App\ValueObjects\LockConfiguration\LockConfiguration;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Foundation\Bus\Dispatchable;

class NeedConfigurationHandler
{
    use Dispatchable;

    public function __construct(private Message $message, private Lockpick $lockpick)
    {
    }

    public function handle(): void
    {
        $invalid = $this->isConfigInvalid($configTxt = $this->message->text());
        $lockpick = $this->lockpick;
        if ($invalid) {
            $lockpick->chat->message(__('telegram_bot.invalid_config') . __('telegram_bot.config_rule'))->send();
            return;
        }
        $lockpick->lock_configuration = $this->makeConfig($configTxt);
        $lockpick->lock_levers_count = count($lockpick->lock_state->levers());
        $lockpick->status_id = LockpickStatus::firstByName(Status::CONFIGURATION)->id;
        $lockpick->save();
        $lockpick->chat->message(__('telegram_bot.config_valid') . __('telegram_bot.state_rule'))->send();
    }

    private function isConfigInvalid(string $configuration): bool
    {
        if (!preg_match('/^(\d+):\[(.*)]$/', $configuration, $matches)) {
            return true;
        }

        $leversCount = (int) $matches[1];
        $groups = explode(',', trim($matches[2]));

        if (count($groups) !== $leversCount) {
            return true;
        }

        foreach ($groups as $index => $group) {
            $group = trim($group);
            if ($group === '') {
                continue;
            }
            $parts = preg_split('/\s+/', $group);
            $seen = [];
            foreach ($parts as $part) {
                if (!preg_match('/^(\d+)([+\-])$/', $part, $m)) {
                    return true;
                }
                $num = (int) $m[1];
                if ($num < 1 || $num > $leversCount) {
                    return true;
                }
                if ($num === $index + 1) {
                    return true;
                }
                if (isset($seen[$num])) {
                    return true;
                }
                $seen[$num] = true;
            }
        }

        return false;
    }

    private function makeConfig(string $configuration): LockConfiguration
    {
        preg_match('/^(\d+):\[(.*)]$/', $configuration, $matches);
        $groups = explode(',', trim($matches[2]));

        $levers = [];
        foreach ($groups as $index => $group) {
            $group = trim($group);
            $affects = [];
            if ($group !== '') {
                $parts = preg_split('/\s+/', $group);
                foreach ($parts as $part) {
                    preg_match('/^(\d+)([+\-])$/', $part, $m);
                    $affects[] = new LeverAffect(
                        number: (int) $m[1],
                        direction: $m[2] === '+' ? Direction::TOGETHER : Direction::SEPARATE,
                    );
                }
            }
            $levers[] = new LeverConfiguration(number: $index + 1, affects: $affects);
        }

        return new LockConfiguration($levers);
    }
}
