<?php

namespace ArtisanBuild\Scalpels\Commands;

use Illuminate\Console\Command;

class ScalpelsCommand extends Command
{
    public $signature = 'scalpels';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
