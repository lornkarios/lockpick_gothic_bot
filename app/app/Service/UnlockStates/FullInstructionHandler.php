<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Models\Lockpick;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Foundation\Bus\Dispatchable;

class FullInstructionHandler
{
    use Dispatchable;

    public function __construct(private Lockpick $lockpick)
    {
    }

    public function handle(): void
    {
        $instruction = $this->generateInstruction();
        $this->lockpick->chat->message($instruction)
            ->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                Button::make(__('telegram_bot.step_by_step'))->action('step_by_step'),
                Button::make(__('telegram_bot.full_instruction'))->action('full_instruction'),
            ]))
            ->send();
    }

    private function generateInstruction(): string
    {
        $histories = $this->lockpick->history()->orderBy('id')->get();
        $steps = [];
        $stepNumber = 1;

        foreach ($histories as $history) {
            if ($history->lever_number === null) {
                continue;
            }

            $direction = $history->is_up ? __('telegram_bot.direction_up') : __('telegram_bot.direction_down');
            $steps[] = __('telegram_bot.instruction_step', [
                'step' => $stepNumber,
                'lever' => $history->lever_number,
                'direction' => $direction,
            ]);
            $stepNumber++;
        }

        return implode("\n", $steps);
    }
}
