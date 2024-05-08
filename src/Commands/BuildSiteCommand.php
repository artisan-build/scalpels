<?php

namespace ArtisanBuild\Scalpels\Commands;

use ArtisanBuild\Scalpels\Actions\AddScriptsToComposerJson;
use ArtisanBuild\Scalpels\Actions\IsPackageInstalled;
use ArtisanBuild\Scalpels\Actions\RegisterHoneybadgerInExceptionHandler;
use Illuminate\Console\Command;

use function Laravel\Prompts\confirm;

class BuildSiteCommand extends Command
{
    public $signature = 'build:site';

    public $description = 'Does some initial configuration of the site';

    public function handle(): int
    {
        /*$packages = [
            'require' => collect(config('scalpels.composer_require')),
            'dev' => collect(config('scalpels.composer_require_dev'))
        ];

        foreach (config('scalpels.composer_require_prompt') as $package) {
            if (confirm("Do you want to require $package?", true)) {
                $packages['require']->push($package);
            }
        }

        foreach (config('scalpels.composer_require_dev_prompt') as $package) {
            if (confirm("Do you want to require --dev $package?", true)) {
                $packages['dev']->push($package);
            }
        }

        $this->info('Installing required packages...');

        shell_exec('composer require ' . $packages['require']->implode(' '));


        $this->info('Installing developer packages...');


        shell_exec('composer require --dev ' . $packages['dev']->implode(' '));*/

        if (app(IsPackageInstalled::class)('larastan/larastan')) {
            $this->info('Configuring PHPStan at level 5. Edit phpstan.neon to your liking.');

            $phpstanConfigPath = base_path('phpstan.neon');
            if (! file_exists($phpstanConfigPath)) {
                copy(__DIR__.'/../../stubs/phpstsan.neon.stub', $phpstanConfigPath);
            }
        }

        if (app(IsPackageInstalled::class)('honeybadger-io/honeybadger-laravel')) {
            $this->info('Configuring Honeybadger');
            app(RegisterHoneybadgerInExceptionHandler::class)();

        }

        app(AddScriptsToComposerJson::class)();

        $this->comment('All done');

        return self::SUCCESS;
    }
}
