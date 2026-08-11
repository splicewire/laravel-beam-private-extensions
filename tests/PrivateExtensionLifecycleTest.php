<?php

use Splicewire\Beam\PrivateExtensions\Exceptions\InvalidCapabilityException;
use Splicewire\Beam\PrivateExtensions\Exceptions\MountPointOccupiedException;
use Splicewire\Beam\PrivateExtensions\Models\PrivateExtension;
use Splicewire\Beam\PrivateExtensions\PrivateExtensions;

beforeEach(function () {
    $this->action = app(PrivateExtensions::class);
});

// ── registration ─────────────────────────────────────────────────────────────────────────

it('registers a Private Extension as active with trust_tier always untrusted_publisher', function () {
    $ext = $this->action->register(
        name: 'Shoot-day checklist',
        mountPoint: 'studio-sidebar',
        bundleSource: "function bridge() {}\nrender(view());\n",
        manifestCapabilities: ['resolve'],
    );

    expect($ext->name)->toBe('Shoot-day checklist')
        ->and($ext->mount_point)->toBe('studio-sidebar')
        ->and($ext->trust_tier)->toBe('untrusted_publisher')
        ->and($ext->active)->toBeTrue();
});

it('never adopts an uploaded manifest tier claim — always assigns untrusted_publisher', function () {
    // A manifest claiming first_party is read informational-only (see PrivateExtensions::register's
    // $manifestTier doc) — the stored tier is never influenced by it.
    $ext = $this->action->register(
        name: 'Sneaky manifest',
        mountPoint: 'dashboard-widget',
        bundleSource: 'render(h("Text", {text: "hi"}));',
        manifestCapabilities: ['resolve'],
        manifestTier: 'first_party',
    );

    expect($ext->trust_tier)->toBe('untrusted_publisher');
});

it('captures the raw bundle source verbatim — byte-for-byte, no transform', function () {
    $source = "  function view() {\n\treturn h('Card', {}, []);\n  }\n\nrender(view());\n  ";

    $ext = $this->action->register(
        name: 'Verbatim check',
        mountPoint: 'settings-tab',
        bundleSource: $source,
        manifestCapabilities: [],
    );

    expect($ext->fresh()->bundle_source)->toBe($source);
});

it('stores manifest.capabilities exactly as declared', function () {
    $ext = $this->action->register(
        name: 'Capabilities check',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(null);',
        manifestCapabilities: ['resolve', 'read_scoped'],
    );

    expect($ext->fresh()->manifest_capabilities)->toBe(['resolve', 'read_scoped']);
});

it('rejects a manifest capability outside the confirmed CapabilityName union before registration completes', function () {
    expect(fn () => $this->action->register(
        name: 'Over-broad ask',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(null);',
        manifestCapabilities: ['resolve', 'delete_everything'],
    ))->toThrow(InvalidCapabilityException::class);

    expect(PrivateExtension::query()->count())->toBe(0);
});

it('accepts every member of the real CapabilityName union: resolve, read_scoped, request_save', function () {
    $ext = $this->action->register(
        name: 'Full union',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(null);',
        manifestCapabilities: ['resolve', 'read_scoped', 'request_save'],
    );

    expect($ext->manifest_capabilities)->toBe(['resolve', 'read_scoped', 'request_save']);
});

// ── one active extension per mount point ────────────────────────────────────────────────

it('rejects a second active registration on an occupied mount point, leaving the existing active record unaltered', function () {
    $first = $this->action->register(
        name: 'First',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );

    expect(fn () => $this->action->register(
        name: 'Second (should be refused)',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(2);',
        manifestCapabilities: [],
    ))->toThrow(MountPointOccupiedException::class);

    expect(PrivateExtension::query()->where('mount_point', 'studio-sidebar')->count())->toBe(1);

    $stillFirst = $first->fresh();
    expect($stillFirst->id)->toBe($first->id)
        ->and($stillFirst->name)->toBe('First')
        ->and($stillFirst->active)->toBeTrue()
        ->and($stillFirst->updated_at->timestamp)->toBe($first->updated_at->timestamp);
});

it('allows registering into a mount point once its sole occupant is deactivated', function () {
    $first = $this->action->register(
        name: 'First',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );
    $this->action->deactivate($first);

    $second = $this->action->register(
        name: 'Second',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(2);',
        manifestCapabilities: [],
    );

    expect($second->active)->toBeTrue()
        ->and($first->fresh()->active)->toBeFalse()
        ->and(PrivateExtension::query()->where('mount_point', 'studio-sidebar')->count())->toBe(2);
});

it('refuses re-activating into a mount point another active extension already occupies', function () {
    $inactiveOne = $this->action->register(
        name: 'A',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );
    $this->action->deactivate($inactiveOne);

    $activeTwo = $this->action->register(
        name: 'B',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(2);',
        manifestCapabilities: [],
    );

    expect(fn () => $this->action->activate($inactiveOne))->toThrow(MountPointOccupiedException::class);
    expect($inactiveOne->fresh()->active)->toBeFalse()
        ->and($activeTwo->fresh()->active)->toBeTrue();
});

// ── toggle active/inactive vs. hard delete ──────────────────────────────────────────────

it('deactivates without deleting — the record persists and the slot frees up', function () {
    $ext = $this->action->register(
        name: 'Toggle me',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );

    $this->action->deactivate($ext);

    expect(PrivateExtension::query()->count())->toBe(1)
        ->and($ext->fresh()->active)->toBeFalse();
});

it('hard-deletes, distinct from deactivation — the row is gone entirely', function () {
    $ext = $this->action->register(
        name: 'Delete me',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );

    $this->action->delete($ext);

    expect(PrivateExtension::query()->count())->toBe(0);
});

it('leaves the mount-point slot in exactly one of three states: no record, an inactive record, or one active record', function () {
    $mountPoint = 'studio-sidebar';

    // State: no record.
    expect(PrivateExtension::query()->where('mount_point', $mountPoint)->count())->toBe(0);

    // State: one active record.
    $ext = $this->action->register(
        name: 'Occupant',
        mountPoint: $mountPoint,
        bundleSource: 'render(1);',
        manifestCapabilities: [],
    );
    $rows = PrivateExtension::query()->where('mount_point', $mountPoint)->get();
    expect($rows)->toHaveCount(1)->and($rows->first()->active)->toBeTrue();

    // State: an inactive record (slot freed, record persists).
    $this->action->deactivate($ext);
    $rows = PrivateExtension::query()->where('mount_point', $mountPoint)->get();
    expect($rows)->toHaveCount(1)->and($rows->first()->active)->toBeFalse();

    // Back to: no record.
    $this->action->delete($ext);
    expect(PrivateExtension::query()->where('mount_point', $mountPoint)->count())->toBe(0);
});
