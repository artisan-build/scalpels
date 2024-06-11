<?php

namespace ArtisanBuild\Scalpels\Actions;

use ArtisanBuild\Support\CurrentEnvironment\ReadComposer;
use ArtisanBuild\Support\CurrentEnvironment\WriteComposer;

class AddScriptsToComposerJson
{
    public function __invoke()
    {
        $json = app(ReadComposer::class);

        $scripts = collect($json['scripts']);

        if (! $scripts->keys()->contains('test')) {
            $scripts->put('test', ['@php artisan test']);
        }

        if (! $scripts->keys()->contains('lint')) {
            $scripts->put('lint', ['vendor/bin/pint']);
        }

        if (! $scripts->keys()->contains('stan')) {
            $scripts->put('stan', ['vendor/bin/phpstan analyse --memory-limit=512M']);
        }

        if (! $scripts->keys()->contains('coverage')) {
            $scripts->put('coverage', [
                'XDEBUG_MODE=coverage ./vendor/bin/pest --coverage-php coverage.php',
                '@php artisan build:code-coverage-html',
            ]);
        }

        if (! $scripts->keys()->contains('ready')) {
            $scripts->put('ready', [
                '@php artisan ide-helper:models --write',
                'composer lint',
                'composer stan',
                'composer test',
            ]);
        }

        $json['scripts'] = $scripts->toArray();

        app(WriteComposer::class)($json);

    }
}
