<?php

namespace Splicewire\Beam\PrivateExtensions;

use Illuminate\Support\ServiceProvider;

/**
 * Tenant-private, non-listed frame-remote extension authoring (splicewire-marketplace-build ticket
 * 16). Registers {@see PrivateExtensions} as the sole action surface for the record lifecycle
 * (register/activate/deactivate/delete). Tables are prefixed by beam core (`Beam::table()`).
 */
class BeamPrivateExtensionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/beam/private-extensions.php', 'beam.private-extensions');

        $this->app->singleton(PrivateExtensions::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/beam/private-extensions.php' => $this->app->configPath('beam/private-extensions.php'),
            ], 'beam-private-extensions-config');
        }

        if (config('beam.private-extensions.register_migrations', true)) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
