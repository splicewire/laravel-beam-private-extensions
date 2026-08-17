<?php

namespace Splicewire\Beam\PrivateExtensions\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Splicewire\Beam\Facades\Beam;
use Splicewire\Beam\PrivateExtensions\PrivateExtensions;

/**
 * A tenant-authored, tenant-private frame-remote extension (splicewire-marketplace-build ticket
 * 16). Exactly five meaningful columns — `name`, `mount_point`, the raw `bundle_source` verbatim as
 * `bridge.load()` receives it (never parsed/transformed — see
 * {@see PrivateExtensions::register()}), `manifest_capabilities`
 * (the `@schemastud/frame-remote` `CapabilityName` union, json array), and `active`. `trust_tier` is
 * always `untrusted_publisher`, written only by the action class — no mutator on this model, no
 * request input, ever sets it to anything else.
 *
 * @property string $id
 * @property string $name
 * @property string $mount_point
 * @property string $bundle_source
 * @property array<int, string> $manifest_capabilities
 * @property string $trust_tier
 * @property bool $active
 */
class PrivateExtension extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'manifest_capabilities' => 'array',
        'active' => 'boolean',
    ];

    public function getTable(): string
    {
        return Beam::table('private_extensions');
    }
}
