<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickHistory;
use App\Models\LockpickStatus;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Foundation\Bus\Dispatchable;

class StepByStepHandler
{
    use Dispatchable;

    public function __construct(
        private Lockpick $lockpick,
        private bool $advance = false,
    ) {
    }

    public function handle(): void
    {
        if ($this->advance) {
            $this->nextStep();
        } else {
            $this->start();
        }
    }

    private function start(): void
    {
        $histories = $this->lockpick->history()->orderBy('id')->get();
        /** @var LockpickHistory $firstStep */
        $firstStep = $histories->first(fn(LockpickHistory $h) => $h->lever_number !== null);

        $this->lockpick->status_id = LockpickStatus::firstByName(Status::STEP_BY_STEP_UNLOCKING)->id;
        $this->lockpick->lockpick_history_id = $firstStep->id;
        $this->lockpick->save();

        $this->sendStep($firstStep);
    }

    private function nextStep(): void
    {
        $currentHistory = $this->lockpick->historyStep;

        /** @var LockpickHistory $nextHistory */
        $nextHistory = LockpickHistory::query()
            ->where('lockpick_id', $this->lockpick->id)
            ->where('id', '>', $currentHistory->id)
            ->whereNotNull('lever_number')
            ->orderBy('id')
            ->first();

        if ($nextHistory) {
            $this->lockpick->lockpick_history_id = $nextHistory->id;
            $this->lockpick->save();

            $this->sendStep($nextHistory);
        } else {
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKED)->id;
            $this->lockpick->lockpick_history_id = null;
            $this->lockpick->save();

            $this->lockpick->chat->message(__('telegram_bot.step_final'))
                ->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                    Button::make(__('telegram_bot.step_by_step'))->action('step_by_step'),
                    Button::make(__('telegram_bot.full_instruction'))->action('full_instruction'),
                ]))
                ->send();
        }
    }

    private function sendStep(LockpickHistory $history): void
    {
        $direction = $history->is_up ? __('telegram_bot.direction_up') : __('telegram_bot.direction_down');

        $this->lockpick->chat->message(
            __('telegram_bot.step_message', [
                'state' => implode(', ', $history->lock_state->toArray()),
                'lever' => $history->lever_number,
                'direction' => $direction,
            ])
        )->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
            Button::make(__('telegram_bot.next_step'))->action('next_step'),
        ]))->send();
    }
}
