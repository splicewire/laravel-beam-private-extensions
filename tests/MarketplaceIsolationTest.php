<?php

use Illuminate\Support\Facades\DB;
use Splicewire\Beam\PrivateExtensions\PrivateExtensions;

/**
 * splicewire-marketplace-build ticket 16, acceptance checklist item 5: registering, activating,
 * deactivating, or deleting a Private Extension must never create a `Listing`, `Seller`, or
 * review-workflow record of any kind. `laravel-beam-market` (where those real tables live) has not
 * landed its Listing/Seller/review schema at the time this ticket was built and this package must
 * never require it regardless — so `TestCase::createFixtureSchema()` stands up local-only tables
 * (`sellers`, `beam_market_products` — ticket 03/07's documented Listing companion table name — and
 * a generic `listing_review_workflow_records`) purely so this test has something concrete to assert
 * zero rows against. Nothing in `PrivateExtensions` (src/PrivateExtensions.php) references any of
 * these table names — the zero count below is a consequence of that, not a coincidence this test
 * manufactures.
 */
it('writes zero rows to Listing/Seller/review-workflow stand-in tables across the full lifecycle', function () {
    $action = app(PrivateExtensions::class);

    $ext = $action->register(
        name: 'Isolation check',
        mountPoint: 'studio-sidebar',
        bundleSource: 'render(1);',
        manifestCapabilities: ['resolve', 'read_scoped'],
    );
    $action->deactivate($ext);
    $action->activate($ext);
    $action->deactivate($ext);
    $action->delete($ext);

    expect(DB::table('sellers')->count())->toBe(0)
        ->and(DB::table('beam_market_products')->count())->toBe(0)
        ->and(DB::table('listing_review_workflow_records')->count())->toBe(0);
});
