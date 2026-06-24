<?php

namespace App\Console\Commands;

use App\Service\Poll\PollHandler;
use Illuminate\Console\Command;

class BotPoll extends Command
{

    protected $signature = 'bot:poll';

    protected $description = 'Manual polling and handle updates';

    public function handle(PollHandler $pollHandler): int
    {
        $pollHandler->handle();

        return self::SUCCESS;
    }
}
