<?php

use Splicewire\Beam\PrivateExtensions\Models\PrivateExtension;

return [
    // Host-overridable model binding (the model-binding seam). A host that subclasses
    // PrivateExtension points the config at its own class; the action resolves through here.
    'models' => [
        'private_extension' => PrivateExtension::class,
    ],

    // Table-prefix note: prefixing is beam core's job — the model calls Beam::table() directly.
];
