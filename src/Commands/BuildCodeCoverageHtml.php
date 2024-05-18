<?php

namespace ArtisanBuild\Scalpels\Commands;

use Illuminate\Console\Command;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;

class BuildCodeCoverageHtml extends Command
{

    public $signature = 'build:code-coverage-html';

    public $description = 'Generate a code coverage HTML file';

    public function handle(): int
    {
        if (! file_exists(base_path('coverage.php'))) {
            $this->error('No code coverage report has been generated.');

            return self::FAILURE;
        }

        $coverage = include base_path('coverage.php');

        (new HtmlReport())->process($coverage, public_path('coverage'));

        $this->info('See your coverage report at '.url('/coverage/index.html'));

        return self::SUCCESS;
    }
}
