<?php

namespace Splicewire\Beam\PrivateExtensions\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Splicewire\Beam\Facades\Beam;
use Splicewire\Beam\PrivateExtensions\BeamPrivateExtensionsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createFixtureSchema();
    }

    protected function getPackageProviders($app): array
    {
        return [
            BeamPrivateExtensionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $c = $app['config'];
        $c->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $c->set('database.default', 'testing');
        $c->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        // The real migration (tenant/, publish-only) leans on Postgres-only current-schema guards
        // (tenant-safe, mirroring laravel-beam-bookmarks' convention) — the standalone suite
        // hand-builds the schema below on sqlite instead, including the same partial unique index
        // the real migration ships. Publish-only migrations never auto-load, so no toggle is needed
        // to keep this package's own harness from double-creating the table.
    }

    protected function createFixtureSchema(): void
    {
        Schema::create(Beam::table('private_extensions'), function (Blueprint $t): void {
            $t->uuid('id')->primary();
            $t->string('name');
            $t->string('mount_point')->index();
            $t->text('bundle_source');
            $t->json('manifest_capabilities');
            $t->string('trust_tier')->default('untrusted_publisher');
            $t->boolean('active')->default(true);
            $t->timestamps();
        });

        DB::statement(sprintf(
            'create unique index %s on %s (mount_point) where active = 1',
            Beam::table('private_extensions').'_active_mount_point_unique',
            Beam::table('private_extensions'),
        ));

        // Stand-in tables for laravel-beam-market's Listing/Seller/review-workflow schema (ticket
        // 16's own acceptance checklist item 5 requires asserting zero rows are ever written to
        // them). laravel-beam-market has not landed these yet (blocked-on-01-only in this ticket, and
        // this package must not require it regardless) — these are test-local stand-ins only, never
        // shipped in this package's own migrations, so a real host still gets the real thing from the
        // real package. Naming mirrors ticket 03/07's documented shape: `sellers`, the Listing
        // companion `beam_market_products`, and a generic `review-workflow` record table.
        Schema::create('sellers', function (Blueprint $t): void {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });

        Schema::create('beam_market_products', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('seller_id');
            $t->string('trust_tier')->nullable();
            $t->timestamps();
        });

        Schema::create('listing_review_workflow_records', function (Blueprint $t): void {
            $t->id();
            $t->unsignedBigInteger('product_id');
            $t->string('state');
            $t->timestamps();
        });
    }
}
