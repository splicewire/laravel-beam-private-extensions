<?php

namespace Splicewire\Beam\PrivateExtensions\Exceptions;

use InvalidArgumentException;
use Splicewire\Beam\PrivateExtensions\Support\CapabilityName;

/**
 * Thrown when a manifest declares a capability outside the confirmed `CapabilityName` union
 * (`resolve` / `read_scoped` / `request_save`). Raised before registration completes — no row is
 * written for a manifest that fails this check.
 */
class InvalidCapabilityException extends InvalidArgumentException
{
    public static function forCapability(string $capability): self
    {
        $allowed = implode(', ', CapabilityName::VALUES);

        return new self("Capability \"{$capability}\" is not in the confirmed CapabilityName union ({$allowed}).");
    }
}
