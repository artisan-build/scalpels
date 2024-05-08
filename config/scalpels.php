<?php

// config for ArtisanBuild/Scalpels
return [
    'composer_require' => [
        'barryvdh/laravel-debugbar',
        'barryvdh/laravel-ide-helper',
        'honeybadger-io/honeybadger-laravel',
        'laravel/folio',
        'laravel/prompts',

    ],
    'composer_require_dev' => [
        'larastan/larastan',
        'laravel/pint',
        'roave/security-advisories',
    ],

    // The command will prompt for these one at a time and install them if you want them
    'composer_require_prompt' => [
        'livewire/livewire',
        'filament/filament',
        'laravel/pennant',
        'laravel/pulse',
        'spatie/db-dumper',
        'spatie/laravel-markdown',
        'spatie/php-structure-discoverer',
    ],

    // The command will prompt for these one at a time and install them if you want them
    'composer_require_dev_prompt' => [
        'symfony/var-exporter',
    ],
];
