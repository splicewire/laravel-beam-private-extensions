<?php

namespace Splicewire\Beam\PrivateExtensions;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Splicewire\Beam\Install\BeamInstallManifest;

/**
 * Tenant-private, non-listed frame-remote extension authoring (splicewire-marketplace-build ticket
 * 16). Registers {@see PrivateExtensions} as the sole action surface for the record lifecycle
 * (register/activate/deactivate/delete). Tables are prefixed by beam core (`Beam::table()`).
 *
 * Migration ships as a PUBLISH-ONLY spatie/laravel-package-tools stub (the estate-wide beam-family
 * convention) under `tenant/` — corrected from an earlier central-only `loadMigrationsFrom()`
 * classification, see the migration's own docblock. Publish-only makes the former
 * `register_migrations` config toggle redundant (retired along with it, same as tower's own
 * conversion dropped `tower.register_migrations`) — a host now runs
 * `vendor:publish --tag=beam-private-extensions-migrations` once, same as every other beam-family
 * package.
 */
class BeamPrivateExtensionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-beam-private-extensions')
            ->hasMigrations([
                'tenant/create_private_extensions_table',
            ]);
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/beam/private-extensions.php', 'beam.private-extensions');

        $this->app->singleton(PrivateExtensions::class);
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/beam/private-extensions.php' => $this->app->configPath('beam/private-extensions.php'),
            ], 'beam-private-extensions-config');
        }

        // Self-register into beam-core's install manifest so `splicewire:beam:install` publishes
        // this package's migration (tenant/, one tag) with the rest of the stack.
        if ($this->app->bound(BeamInstallManifest::class)) {
            $this->app->make(BeamInstallManifest::class)->register(
                package: 'splicewire/laravel-beam-private-extensions',
                publishTags: ['beam-private-extensions-migrations'],
                migrates: true,
            );
        }
    }
}
