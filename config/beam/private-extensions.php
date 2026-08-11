<?php

use Splicewire\Beam\PrivateExtensions\Models\PrivateExtension;

return [
    // Host-overridable model binding (the model-binding seam). A host that subclasses
    // PrivateExtension points the config at its own class; the action resolves through here.
    'models' => [
        'private_extension' => PrivateExtension::class,
    ],

    // Load the package migrations. A host that publishes/owns its own copies sets this false.
    'register_migrations' => env('BEAM_PRIVATE_EXTENSIONS_REGISTER_MIGRATIONS', true),

    // Table-prefix note: prefixing is beam core's job — the model calls Beam::table() directly.
];
