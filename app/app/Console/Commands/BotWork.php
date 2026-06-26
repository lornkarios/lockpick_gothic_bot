<?php

namespace App\Console\Commands;

use App\Service\Poll\PollHandler;
use Illuminate\Console\Command;
use Throwable;

class BotWork extends Command
{

    protected $signature = 'bot:work';

    protected $description = 'Manual polling and handle updates';

    public function handle(PollHandler $pollHandler): int
    {
        while (true) {
            try {
                $pollHandler->handle();
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
