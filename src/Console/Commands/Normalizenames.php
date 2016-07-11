<?php

namespace Swis\GotLaravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Swis\GotLaravel\Models\ResultsRepository;

class Normalizenames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'got:normalize-names';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalize names';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Normalizing author names');
        ResultsRepository::normalizeNames();
    }
}
