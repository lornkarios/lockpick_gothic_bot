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
use Generator;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UnlockHandler
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

            $this->lockpick->history()->create([
                'lock_state' => new LockState($levers),
//                'is_up' => $historyState['is_up'] ?? null,
//                'lever_number' => $historyState['lever_number'] ?? null,
            ]);
        }
    }

    private function unlock(
        Lock $lock
    ): ?int {
        $successState = $this->getSuccessLockState($lock);
        $generator = $this->depthFirstSearchGenerator($lock, $this->encodeState($lock->state()->toArray()));
        foreach ($generator as $state) {
            if ($successState === $state) {
                return $state;
            }
        }
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

    private function depthFirstSearchGenerator(Lock $lock, int $state): Generator
    {
        $stack = [$state];
        $history = [$state];
        $this->parent = [];
        $child = [];
        $iteration = 0;
        while (count($stack) > 0) {
            $curState = array_pop($stack);

            // Возвращаем узел лениво, не сохраняя в массив
            yield $curState;

            $finishStates = $this->finishStates($lock, $curState, $history);
            $history = array_unique(array_merge($history, $finishStates));
            foreach ($finishStates as $finishState) {
                $iteration++;
                array_unshift($stack, $finishState);
                $this->parent[$finishState] = $curState;
                $child[$curState] = $finishState;
            }
            if ($iteration > 20000) {
                $this->depth($state, $child);
            }
        }
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


//
///
///                      1
///                1    2    3
///            1 2 3  1 2 3 1 2 3
///
///


    private function finishStates(
        Lock $lock,
        int $finishState,
        array $history
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
        array $history,
    ): ?int {
        if (!$this->canMove($lock, $leverNumber, $direction)) {
            return null;
        }
        $this->move($lock, $leverNumber, $direction);
        $state = $this->encodeState($lock->state()->toArray());
        $this->move($lock, $leverNumber, $this->inverseDirection($direction));
        if (in_array($state, $history)) {
            return null;
        }
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
