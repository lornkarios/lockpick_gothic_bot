<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickHistory;
use App\Models\LockpickStatus;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
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
        $initialState = $histories->first();

        $this->lockpick->status_id = LockpickStatus::firstByName(Status::STEP_BY_STEP_UNLOCKING)->id;
        $this->lockpick->lockpick_history_id = $initialState->id;
        $this->lockpick->save();

        $this->sendStep($initialState);
    }

    private function nextStep(): void
    {
        $currentHistory = $this->lockpick->historyStep;

        $actionRecord = $this->nextActionAfter($currentHistory->id);

        if (!$actionRecord) {
            return;
        }

        $this->lockpick->lockpick_history_id = $actionRecord->id;
        $this->lockpick->save();

        $nextAction = $this->nextActionAfter($actionRecord->id);

        if ($nextAction) {
            $this->sendStep($actionRecord);
        } else {
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKED)->id;
            $this->lockpick->lockpick_history_id = null;
            $this->lockpick->save();

            $this->lockpick->chat->message(
                __('telegram_bot.step_final', [
                    'visual' => $this->renderVisual($actionRecord->lock_state),
                ])
            )->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                Button::make(__('telegram_bot.step_by_step'))->action('step_by_step'),
                Button::make(__('telegram_bot.full_instruction'))->action('full_instruction'),
            ]))->send();
        }
    }

    private function sendStep(LockpickHistory $currentState): void
    {
        $actionRecord = $this->nextActionAfter($currentState->id);

        if (!$actionRecord) {
            return;
        }

        $direction = $actionRecord->is_up ? __('telegram_bot.direction_up') : __('telegram_bot.direction_down');

        $this->lockpick->chat->message(
            __('telegram_bot.step_message', [
                'visual' => $this->renderVisual($currentState->lock_state),
                'lever' => $actionRecord->lever_number,
                'direction' => $direction,
            ])
        )->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
            Button::make(__('telegram_bot.next_step'))->action('next_step'),
        ]))->send();
    }

    private function nextActionAfter(int $historyId): ?LockpickHistory
    {
        return LockpickHistory::query()
            ->where('lockpick_id', $this->lockpick->id)
            ->where('id', '>', $historyId)
            ->whereNotNull('lever_number')
            ->orderBy('id')
            ->first();
    }

    private function renderVisual(LockState $state): string
    {
        $positions = $state->toArray();
        $rows = [];

        for ($pos = LeverState::MIN_POSITION; $pos <= LeverState::MAX_POSITION; $pos++) {
            $row = [];
            foreach ($positions as $leverPos) {
                $row[] = $leverPos === $pos ? '0' : '-';
            }
            $rows[] = implode(' ', $row);
        }

        return implode("\n", $rows);
    }
}
