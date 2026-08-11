<?php

namespace Splicewire\Beam\PrivateExtensions\Exceptions;

use RuntimeException;
use Splicewire\Beam\PrivateExtensions\PrivateExtensions;

/**
 * Thrown when registering a Private Extension against a mount point that already holds an active
 * extension. The existing active record is left completely unaltered — this exception fires before
 * any write happens (checked inside the same transaction as the insert, see
 * {@see PrivateExtensions::register()}).
 */
class MountPointOccupiedException extends RuntimeException
{
    public static function forMountPoint(string $mountPoint): self
    {
        return new self("Mount point \"{$mountPoint}\" already has an active Private Extension registered.");
    }
}
