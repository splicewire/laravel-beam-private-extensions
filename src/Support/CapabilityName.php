<?php

namespace Splicewire\Beam\PrivateExtensions\Support;

/**
 * A manual PHP-side duplicate of `@schemastud/frame-remote`'s `CapabilityName` union
 * (`src/host/tiers.ts`). No PHP-side counterpart of `@schemastud/frame-remote` exists in the fleet
 * (confirmed by search — the package is pure TS/JS, consumed only from the UI), so there is nothing
 * to `composer require`; this is the "duplicate the vendored package's relevant conventions" the
 * ticket asks for, kept manually in sync rather than imported.
 *
 * @see AGENTS.md's "Vendored family-package conventions" section for the sync obligation.
 */
final class CapabilityName
{
    /** The exact three-member union — must stay byte-for-byte identical to `tiers.ts`'s `CapabilityName`. */
    public const VALUES = ['resolve', 'read_scoped', 'request_save'];

    public static function isValid(string $name): bool
    {
        return in_array($name, self::VALUES, true);
    }

    /**
     * @param  array<int, mixed>  $capabilities
     */
    public static function allValid(array $capabilities): bool
    {
        foreach ($capabilities as $capability) {
            if (! is_string($capability) || ! self::isValid($capability)) {
                return false;
            }
        }

        return true;
    }
}
