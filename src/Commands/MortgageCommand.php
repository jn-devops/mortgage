<?php

namespace Homeful\Mortgage\Commands;

use Illuminate\Console\Command;

class MortgageCommand extends Command
{
    public $signature = 'mortgage';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
