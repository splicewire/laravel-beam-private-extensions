<?php

namespace Splicewire\Beam\PrivateExtensions;

use Illuminate\Support\Facades\DB;
use Splicewire\Beam\PrivateExtensions\Exceptions\InvalidCapabilityException;
use Splicewire\Beam\PrivateExtensions\Exceptions\MountPointOccupiedException;
use Splicewire\Beam\PrivateExtensions\Models\PrivateExtension;
use Splicewire\Beam\PrivateExtensions\Support\CapabilityName;

/**
 * Register / activate / deactivate / delete a {@see PrivateExtension} — the one action surface for
 * the whole lifecycle (splicewire-marketplace-build ticket 16). Deliberately the ONLY place a
 * `trust_tier` is written, and it is always `untrusted_publisher`: there is no parameter on
 * {@see self::register()} through which a caller can set it. A manifest's own `tier` claim (if a
 * caller passes one through `$manifestTier`) is accepted purely for provenance/telemetry callers may
 * want to log elsewhere — it is never read back out of this class and never reaches the stored row.
 */
class PrivateExtensions
{
    /**
     * @param  array<int, string>  $manifestCapabilities  Must be a subset of the confirmed
     *                                                    CapabilityName union (`resolve`/`read_scoped`/`request_save`) — anything else refuses
     *                                                    registration before any row is written.
     * @param  string|null  $manifestTier  The uploaded manifest's own advisory `tier` claim, if any.
     *                                     Read here purely informational — never adopted as the assigned tier, never stored, never
     *                                     returned. The host (this class) is the sole authority; it always assigns `untrusted_publisher`.
     */
    public function register(
        string $name,
        string $mountPoint,
        string $bundleSource,
        array $manifestCapabilities,
        ?string $manifestTier = null,
    ): PrivateExtension {
        foreach ($manifestCapabilities as $capability) {
            if (! is_string($capability) || ! CapabilityName::isValid($capability)) {
                throw InvalidCapabilityException::forCapability((string) $capability);
            }
        }

        // $manifestTier is accepted only to make the "informational-only, never adopted" contract
        // explicit at the call site — it is intentionally unused beyond this point.
        unset($manifestTier);

        return DB::transaction(function () use ($name, $mountPoint, $bundleSource, $manifestCapabilities) {
            $occupied = $this->modelClass()::query()
                ->where('mount_point', $mountPoint)
                ->where('active', true)
                ->lockForUpdate()
                ->exists();

            if ($occupied) {
                throw MountPointOccupiedException::forMountPoint($mountPoint);
            }

            return $this->modelClass()::query()->create([
                'name' => $name,
                'mount_point' => $mountPoint,
                'bundle_source' => $bundleSource,
                'manifest_capabilities' => array_values($manifestCapabilities),
                'trust_tier' => 'untrusted_publisher',
                'active' => true,
            ]);
        });
    }

    /**
     * Toggle a Private Extension active — frees its mount-point slot for a new active registration
     * while the record itself persists (not a delete).
     */
    public function deactivate(PrivateExtension $extension): PrivateExtension
    {
        $extension->update(['active' => false]);

        return $extension->refresh();
    }

    /**
     * Re-activate a previously deactivated extension. Subject to the same one-active-per-mount-
     * point invariant as {@see self::register()} — reactivating into an occupied slot is refused.
     */
    public function activate(PrivateExtension $extension): PrivateExtension
    {
        return DB::transaction(function () use ($extension) {
            $occupied = $this->modelClass()::query()
                ->where('mount_point', $extension->mount_point)
                ->where('active', true)
                ->where('id', '!=', $extension->id)
                ->lockForUpdate()
                ->exists();

            if ($occupied) {
                throw MountPointOccupiedException::forMountPoint($extension->mount_point);
            }

            $extension->update(['active' => true]);

            return $extension->refresh();
        });
    }

    /**
     * Hard delete — distinct from {@see self::deactivate()}. Removes the row outright rather than
     * merely freeing the mount-point slot.
     */
    public function delete(PrivateExtension $extension): void
    {
        $extension->delete();
    }

    /**
     * @return class-string<PrivateExtension>
     */
    private function modelClass(): string
    {
        /** @var class-string<PrivateExtension> $class */
        $class = config('beam.private-extensions.models.private_extension', PrivateExtension::class);

        return $class;
    }
}
