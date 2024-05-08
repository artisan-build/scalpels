<?php

namespace ArtisanBuild\Scalpels\Actions;

class IsPackageInstalled
{
    public function __invoke(string $package): bool
    {
        $composer = json_decode(file_get_contents(base_path('composer.lock')), true);

        $packages = collect($composer['packages']);
        $dev = collect($composer['packages-dev']);

        return $packages->where('name', $package)->isNotEmpty() || $dev->where('name', $package)->isNotEmpty();
    }
}
