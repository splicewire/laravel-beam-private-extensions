<?php

/**
 * splicewire-marketplace-build ticket 16, acceptance checklist item 7: this package's composer
 * dependencies must never expand beyond beam-core plus @schemastud/frame-remote's PHP-side
 * counterpart (none exists in the fleet today — confirmed by search, so beam-core alone). A static
 * check on composer.json itself, not a DB/runtime assertion — the surest way to catch a future PR
 * that adds `laravel-beam-market` (or any of its marketplace siblings) to `require`.
 */
it('requires no other splicewire marketplace package beyond beam-core', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);

    $require = array_keys($composer['require']);
    $requireDev = array_keys($composer['require-dev'] ?? []);

    $forbidden = [
        'splicewire/laravel-beam-market',
        'splicewire/laravel-beam-commerce',
        'splicewire/laravel-beam-licenser',
        'splicewire/laravel-beam-workflows',
    ];

    foreach ($forbidden as $package) {
        expect($require)->not->toContain($package);
        expect($requireDev)->not->toContain($package);
    }

    $splicewireRequires = array_values(array_filter($require, fn (string $name) => str_starts_with($name, 'splicewire/')));
    expect($splicewireRequires)->toBe(['splicewire/laravel-beam']);
});
