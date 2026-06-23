<?php

declare(strict_types=1);

namespace App\Enums;

enum Direction: string
{
    case TOGETHER = 'together';
    case SEPARATE = 'separate';
}
