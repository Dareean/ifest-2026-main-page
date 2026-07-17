<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppStart extends Command
{
    protected $signature = 'app:start';

    public function handle(): int
    {
        $this->call('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->call('serve', [
            '--host' => '0.0.0.0',
            '--port' => env('PORT', '8000'),
        ]);

        return Command::SUCCESS;
    }
}
