<?php

namespace ArtisanBuild\Scalpels\Actions;

class RegisterHoneybadgerInExceptionHandler
{
    // TODO: Use Rector and set this up to work with Laravel pre-11
    public function __invoke()
    {
        $appBootstrapPath = base_path('bootstrap/app.php');

        $contents = file_get_contents($appBootstrapPath);

        if (str_contains($contents, '->withExceptions(function (Exceptions $exceptions) {')) {
            $contents = str_replace('->withExceptions(function (Exceptions $exceptions) {', file_get_contents(__DIR__.'/../../stubs/honeybadger_block.stub'), $contents);

            file_put_contents($appBootstrapPath, $contents);
        }
    }
}
