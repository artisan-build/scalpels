<?php

namespace ArtisanBuild\Scalpels\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ArtisanBuild\Scalpels\Scalpels
 */
class Scalpels extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ArtisanBuild\Scalpels\Scalpels::class;
    }
}
