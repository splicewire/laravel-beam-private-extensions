# laravel-beam-private-extensions

Tenant-private, non-listed **frame-remote** extension authoring: register / activate / deactivate /
delete a `PrivateExtension` — structurally cannot create a `Listing`, `Seller`, or review-workflow
record of any kind (splicewire-marketplace-build ticket 16).

- **`PrivateExtension`** (`src/Models/PrivateExtension.php`) — `name`, `mount_point`, the raw
  `bundle_source` verbatim as `bridge.load()` (`@schemastud/frame-remote`) receives it,
  `manifest_capabilities` (json array, constrained to `resolve` / `read_scoped` / `request_save`),
  `trust_tier` (always `untrusted_publisher`), `active`.
- **`PrivateExtensions`** (`src/PrivateExtensions.php`) — the one action surface:
  `register()` / `activate()` / `deactivate()` / `delete()`. One active extension per mount point,
  enforced transactionally plus a defense-in-depth partial unique index.

This is the one genuinely independent branch of the whole marketplace build: it requires only
`splicewire/laravel-beam`, never `laravel-beam-market` or any of its siblings — see `AGENTS.md` for
the full boundary and the frame-remote convention duplication this package carries.

**Not built here:** a live tenant-facing rendering path. See `AGENTS.md` / ticket 16 for what's
still an open gap (the capability-broker/token-mint endpoint, the runtime mount-point resolver).

## Testing

```
composer install
vendor/bin/pest
```
