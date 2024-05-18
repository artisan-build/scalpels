<?php

namespace ArtisanBuild\Scalpels;

use ArtisanBuild\Scalpels\Commands\BuildCodeCoverageHtml;
use ArtisanBuild\Scalpels\Commands\BuildSiteCommand;
use ArtisanBuild\Scalpels\Commands\ScalpelsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ScalpelsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('scalpels')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_scalpels_table')
            ->hasCommand(ScalpelsCommand::class)
            ->hasCommand(BuildCodeCoverageHtml::class)
            ->hasCommand(BuildSiteCommand::class);
    }
}
