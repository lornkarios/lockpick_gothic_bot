<?php

declare(strict_types=1);

namespace App\Models;

use DefStudio\Telegraph\Models\TelegraphBot as BaseBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * DefStudio\Telegraph\Models\TelegraphBot
 *
 * @property int $id
 * @property string $token
 * @property string $name
 * @property int $offset
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, TelegraphChat> $chats
 */
class TelegraphBot extends BaseBot
{

}
