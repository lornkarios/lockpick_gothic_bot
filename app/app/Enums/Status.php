<?php

declare(strict_types=1);

namespace App\Enums;

enum Status: string
{
    case START = 'Start';
    case CONFIGURATION = 'Configuration';
    case UNLOCKING = 'Unlocking';
    case UNLOCKED = 'Unlocked';
    case STEP_BY_STEP_UNLOCKING = 'StepByStepUnlocking';
    case NOT_UNLOCKABLE = 'NotUnlockable';
}
