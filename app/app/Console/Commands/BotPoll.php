<?php

namespace App\Console\Commands;

use App\Service\Poll\PollHandler;
use Illuminate\Console\Command;
use Throwable;

class BotPoll extends Command
{

    protected $signature = 'bot:poll';

    protected $description = 'Manual polling and handle updates';

    public function handle(PollHandler $pollHandler): int
    {
        try {
            $pollHandler->handle();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
        }

        return self::SUCCESS;
    }
}
