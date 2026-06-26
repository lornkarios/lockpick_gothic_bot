<?php

declare(strict_types=1);

namespace App\Service\UnlockStates;

use App\Enums\Status;
use App\Models\Lockpick;
use App\Models\LockpickStatus;
use App\ValueObjects\Lock;
use App\ValueObjects\LockState\LeverState;
use App\ValueObjects\LockState\LockState;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UnlockHandler implements ShouldQueue
{
    use Dispatchable;

    private array $parent;

    public function __construct(private Lockpick $lockpick)
    {
    }

    public function handle(): void
    {
        $state = $this->unlock($this->lockpick->lock);

        if (is_null($state)) {
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::NOT_UNLOCKABLE)->id;
            $this->lockpick->save();
            $this->lockpick->chat->message(__('telegram_bot.unlock_impossible'))->send();
            return;
        }

        DB::transaction(function () use ($state) {
            $this->lockpick->refresh();
            $this->saveHistory($state);
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKED)->id;
            $this->lockpick->save();
        });

        $this->lockpick->chat->message(__('telegram_bot.unlock_success'))
            ->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                Button::make(__('telegram_bot.step_by_step'))->action('step_by_step'),
                Button::make(__('telegram_bot.full_instruction'))->action('full_instruction'),
            ]))
            ->send();
    }

    private function saveHistory(int $state): void
    {
        $histories = [$state];
        while (isset($this->parent[$state])) {
            array_unshift($histories, $this->parent[$state]);
            $state = $this->parent[$state];
        }

        foreach ($histories as $historyState) {
            $levers = [];
            $stateArray = $this->decode($historyState, $this->lockpick->lock_levers_count);
            foreach ($stateArray as $index => $position) {
                $levers[] = new LeverState($index + 1, $position);
            }

            $res = $this->isUpAndLeverNumber($histories, $stateArray);
            $this->lockpick->history()->create([
                'lock_state' => new LockState($levers),
                'is_up' => $res['is_up'] ?? null,
                'lever_number' => $res['lever_number'] ?? null,
            ]);
        }
    }

    private function isUpAndLeverNumber(array $histories, array $historyState): array
    {
        $state = $this->encodeState($historyState);
        $index = array_search($state, $histories, true);

        if ($index === false || $index === 0) {
            return ['is_up' => null, 'lever_number' => null];
        }

        $prevState = $this->decode($histories[$index - 1], count($historyState));
        $config = $this->lockpick->lock_configuration?->toArray() ?? [];

        for ($leverNumber = 1; $leverNumber <= count($historyState); $leverNumber++) {
            $lock = new Lock($prevState, $config);
            if ($lock->canUp($leverNumber)) {
                $lock->up($leverNumber);
                if ($lock->stateToArray() === $historyState) {
                    return ['is_up' => true, 'lever_number' => $leverNumber];
                }
            }

            $lock = new Lock($prevState, $config);
            if ($lock->canDown($leverNumber)) {
                $lock->down($leverNumber);
                if ($lock->stateToArray() === $historyState) {
                    return ['is_up' => false, 'lever_number' => $leverNumber];
                }
            }
        }

        return ['is_up' => null, 'lever_number' => null];
    }

    private function unlock(Lock $lock): ?int
    {
        $successState = $this->getSuccessLockState($lock);
        $state = $this->encodeState($lock->stateToArray());
        $stack = [$state];
        $history = [$state => 1];
        $this->parent = [];
        $child = [];
        while (count($stack) > 0) {
            $curState = array_pop($stack);

            // Возвращаем узел лениво, не сохраняя в массив
            if ($curState === $successState) {
                return $curState;
            }

            $finishStates = $this->finishStates($lock, $curState, $history);
            foreach ($finishStates as $finishState) {
                array_unshift($stack, $finishState);
                $this->parent[$finishState] = $curState;
                $child[$curState] = $finishState;
            }
        }
        return null;
    }

    private function getSuccessLockState(
        Lock $lock
    ): int {
        $unlockedState = [];
        for ($i = 0; $i < $lock->leversCount(); $i++) {
            $unlockedState[] = 4;
        }
        return $this->encodeState($unlockedState);
    }

    private function depth(int $state, array $child): int
    {
        $depth = 0;
        while (isset($child[$state])) {
            $depth++;
            $state = $child[$state];
        }
        return $depth;
    }

    private function finishStates(
        Lock $lock,
        int $finishState,
        array &$history
    ): array {
        $newFinishStates = [];
        $lock->stateFromArray($this->decode($finishState, $lock->leversCount()));
        for ($leverNumber = 1; $leverNumber <= $lock->leversCount(); $leverNumber++) {
            $stepState = $this->unlockStep($lock, $leverNumber, 'up', $history);
            if (!is_null($stepState)) {
                $newFinishStates[] = $stepState;
            }

            $stepState = $this->unlockStep($lock, $leverNumber, 'down', $history);
            if (!is_null($stepState)) {
                $newFinishStates[] = $stepState;
            }
        }
        return $newFinishStates;
    }

    private function unlockStep(
        Lock $lock,
        int $leverNumber,
        string $direction,
        array &$history,
    ): ?int {
        if (!$this->canMove($lock, $leverNumber, $direction)) {
            return null;
        }
        $this->move($lock, $leverNumber, $direction);
        $state = $this->encodeState($lock->stateToArray());
        $this->move($lock, $leverNumber, $this->inverseDirection($direction));
        if ($history[$state] ?? 0 === 1) {
            return null;
        }
        $history[$state] = 1;
        return $state;
    }

    private function inverseDirection(
        string $direction
    ): string {
        return match ($direction) {
            'up' => 'down',
            'down' => 'up',
        };
    }

    private function canMove(
        Lock $lock,
        int $leverNumber,
        string $direction
    ): bool {
        return match ($direction) {
            'up' => $lock->canUp($leverNumber),
            'down' => $lock->canDown($leverNumber),
        };
    }

    private function move(
        Lock $lock,
        int $leverNumber,
        string $direction
    ): void {
        match ($direction) {
            'up' => $lock->up($leverNumber),
            'down' => $lock->down($leverNumber),
        };
    }

    private function encodeState(
        array $s
    ): int {
        $x = 0;
        $length = count($s);

        for ($i = $length - 1; $i >= 0; $i--) {
            // Если передана строка, берем символ как число. Если массив — элемент массива.
            $currentValue = $s[$i];

            $x = $x * 7 + ($currentValue - 1);
        }

        return $x;
    }

    private function decode(
        int $x,
        int $positionsCount
    ): array {
        $s = [];

        // Восстанавливаем разряды числа
        for ($i = 0; $i < $positionsCount; $i++) {
            // Получаем остаток от деления и прибавляем обратно 1
            $currentValue = ($x % 7) + 1;
            array_push($s, $currentValue);

            // Уменьшаем число в 7 раз (целочисленное деление)
            $x = intdiv($x, 7);
        }

        return $s;
    }
}
