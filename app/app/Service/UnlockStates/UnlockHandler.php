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
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class UnlockHandler
{
    use Dispatchable;

    public function __construct(private Lockpick $lockpick)
    {
    }

    public function handle(): void
    {
        $lock = new Lock($this->lockpick->lock_configuration, $this->lockpick->lock_state);

        $result = $this->unlock($lock, [['state' => $lock->state()->toArray()]]);

        if (!$result['status']) {
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::NOT_UNLOCKABLE)->id;
            $this->lockpick->save();
            $this->lockpick->chat->message(
                'Этот замок невозможно взломать :( Введите /start чтобы начать заново'
            )->send();
            return;
        }

        DB::transaction(function () use ($result) {
            $this->saveHistory($result['history']);
            $this->lockpick->status_id = LockpickStatus::firstByName(Status::UNLOCKED)->id;
            $this->lockpick->save();
        });

        $this->lockpick->chat->message('Замок успешно взломан!')
            ->keyboard(fn(Keyboard $keyboard) => $keyboard->buttons([
                Button::make('Пошагово')->action('step_by_step'),
                Button::make('Полная инструкция')->action('full_instruction'),
            ]))
            ->send();
    }

    private function saveHistory(array $historyStates): void
    {
        foreach ($historyStates as $historyState) {
            $levers = [];
            $stateArray = $historyState['state'];
            foreach ($stateArray as $index => $position) {
                $levers[] = new LeverState($index + 1, $position);
            }
            $this->lockpick->history()->create([
                'lock_state' => new LockState($levers),
                'is_up' => $historyState['is_up'] ?? null,
                'lever_number' => $historyState['lever_number'] ?? null,
            ]);
        }
    }

    private function unlock(Lock $lock, array $historyStates = []): array
    {
        if ($lock->isUnlocked()) {
            return ['status' => true, 'history' => $historyStates];
        }
        for ($leverNumber = 1; $leverNumber <= $lock->leversCount(); $leverNumber++) {
            $res = $this->unlockStep($lock, $historyStates, $leverNumber, 'up');
            if (!is_null($res)) {
                return $res;
            }
            $res = $this->unlockStep($lock, $historyStates, $leverNumber, 'down');
            if (!is_null($res)) {
                return $res;
            }
        }

        return ['status' => false, 'history' => $historyStates];
    }

    private function unlockStep(Lock $lock, array $historyStates, int $leverNumber, string $direction): ?array
    {
        if ($this->canMove($lock, $leverNumber, $direction)) {
            $this->move($lock, $leverNumber, $direction);
            if ($this->isStateAlreadyPassed($lock->state()->toArray(), $historyStates)) {
                $this->move($lock, $leverNumber, $this->inverseDirection($direction));
                return ['status' => false, 'history' => $historyStates];
            }
            $res = $this->unlock(
                $lock,
                array_merge(
                    $historyStates,
                    [[
                        'state' => $lock->state()->toArray(),
                        'lever_number' => $leverNumber,
                        'is_up' => $direction === 'up',
                    ]],
                )
            );
            if ($res['status']) {
                $this->move($lock, $leverNumber, $this->inverseDirection($direction));
                return $res;
            }
            $this->move($lock, $leverNumber, $this->inverseDirection($direction));
        }
        return null;
    }

    private function inverseDirection(string $direction): string
    {
        return match ($direction) {
            'up' => 'down',
            'down' => 'up',
        };
    }

    private function canMove(Lock $lock, int $leverNumber, string $direction): bool
    {
        return match ($direction) {
            'up' => $lock->canUp($leverNumber),
            'down' => $lock->canDown($leverNumber),
        };
    }

    private function move(Lock $lock, int $leverNumber, string $direction): void
    {
        match ($direction) {
            'up' => $lock->up($leverNumber),
            'down' => $lock->down($leverNumber),
        };
    }

    private function isStateAlreadyPassed(array $state, array $historyStates): bool
    {
        $states = array_map(fn(array $historyState) => $historyState['state'], $historyStates);
        return in_array($state, $states, true);
    }
}
