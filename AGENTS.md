> You are in **splicewire/laravel-beam-private-extensions** — tenant-private, non-listed frame-remote
> extension authoring: register/activate/deactivate/delete, structurally cannot create a `Listing`,
> `Seller`, or review-workflow record.

`PrivateExtensions` (`src/PrivateExtensions.php`) is the one action surface for the whole lifecycle
of a `PrivateExtension` (`src/Models/PrivateExtension.php`): `name`, `mount_point`, the raw
`bundle_source` verbatim as `bridge.load()` receives it (never parsed/transformed),
`manifest_capabilities` (json array, constrained to the `CapabilityName` union), `trust_tier`
(always `untrusted_publisher` — no field/param anywhere sets it to anything else), and `active`. One
active extension per mount point, enforced in a transaction (defense-in-depth partial unique index
on `(mount_point) WHERE active`). Deactivating frees the mount-point slot without deleting; deleting
is a separate, hard operation.

This is splicewire-marketplace-build's ticket 16 — deliberately **the one genuinely independent
branch** of the whole marketplace effort. It requires only beam-core
(`splicewire/laravel-beam`) plus `@schemastud/frame-remote`'s PHP-side counterpart, which does not
exist in the fleet (confirmed by search: frame-remote is pure TS/JS, consumed only from the UI). It
must never require `laravel-beam-market`, `laravel-beam-commerce`, `laravel-beam-licenser`,
`laravel-beam-workflows`, or any other package this marketplace build produces —
`tests/ComposerDependencyBoundaryTest.php` enforces that statically, and
`tests/MarketplaceIsolationTest.php` proves the full register/activate/deactivate/delete lifecycle
writes zero rows to Listing/Seller/review-workflow stand-in tables.

**Out of scope here, by design:** a live tenant-facing rendering path. No generic capability-
broker/token-mint endpoint and no runtime mount-point resolver exist yet — a registered extension is
not actually served to a real mount point by this package's own work. The wizard + record CRUD are
demonstrable against `splicewire-app`'s dev harness (`PrivateExtensionHarnessPage.tsx`) only. See
ticket 16 (`splicewire-marketplace-build/issues/16-private-extension-authoring-flow.md`) for the
full acceptance checklist and the PRD's Out of Scope section for the follow-on capability-broker
gap.

## Vendored family-package conventions

This package does not (and cannot) `composer require` `@schemastud/frame-remote` — it's a pure
TS/JS package with no PHP-side counterpart. Instead it **manually duplicates** two of its load-
bearing conventions in PHP, and both duplications carry a sync obligation back to the source:

- **The `CapabilityName` union** (`src/Support/CapabilityName.php`) is a byte-for-byte copy of
  `@schemastud/frame-remote`'s `CapabilityName` type (`src/host/tiers.ts`, in
  `~/Workspaces/js/packages/schemastud/frame-remote`): exactly `resolve` / `read_scoped` /
  `request_save`. If frame-remote's union ever changes, this file must change with it — there is no
  automated cross-language sync.
- **"The host assigns the tier; the manifest's own claim is advisory only."** frame-remote's
  `ComponentManifest.tier` (`src/host/manifest.ts`) is documented there as "carried for
  provenance/telemetry, never as the authority" — `evaluateManifest` takes the host-assigned tier as
  its own argument, not the manifest's. `PrivateExtensions::register()`'s `$manifestTier` parameter
  mirrors that exactly: accepted for call-site clarity, never read back out, never stored. The
  stored `trust_tier` is always `untrusted_publisher`, written unconditionally by this class.

Any repo that vendors another family repo's code (composer `vendor/<vendor>/<pkg>/`, npm
`node_modules/<vendor>/<pkg>/`) checks that vendored repo's own `AGENTS.md` for conventions it ships
with itself before editing through into it. (`@schemastud/frame-remote` itself ships no `AGENTS.md`
today — the two conventions above were sourced from its `README.md` and source-file doc comments
instead.)
