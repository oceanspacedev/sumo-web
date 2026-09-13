<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpgradeCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_route_and_legacy_api_prefix_are_available(): void
    {
        $this->get('/up')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/scanqr')->assertOk();
        $this->get('/productqr')->assertOk();
        $this->getJson('/api/user')->assertUnauthorized();
        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/postlogin')->assertRedirect('/login');

        $apiRoutes = collect(app('router')->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'));

        $this->assertNotEmpty($apiRoutes);
        $apiRoutes->each(function ($route): void {
            $this->assertContains('api', $route->middleware());
        });

        $this->assertContains('throttle:api', app('router')->getMiddlewareGroups()['api']);
        $this->assertContains(ValidateCsrfToken::class, app('router')->getMiddlewareGroups()['web']);
    }

    public function test_token_expiry_migration_preserves_existing_rows(): void
    {
        $migration = require base_path(
            'database/migrations/2026_02_04_114039_add_expires_at_to_personal_access_tokens_table.php'
        );
        $migration->down();

        DB::table('personal_access_tokens')->insert([
            'id' => 1,
            'tokenable_type' => \App\Models\User::class,
            'tokenable_id' => 1,
            'name' => 'legacy',
            'token' => 'legacy-token',
        ]);

        try {
            $migration = require base_path(
                'database/migrations/2026_02_04_114039_add_expires_at_to_personal_access_tokens_table.php'
            );
            $migration->up();

            $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
            $this->assertContains(
                'personal_access_tokens_expires_at_index',
                array_column(Schema::getIndexes('personal_access_tokens'), 'name')
            );
            $this->assertSame('legacy-token', DB::table('personal_access_tokens')->value('token'));
        } finally {
            DB::table('personal_access_tokens')->delete();
        }
    }

    public function test_preexisting_expiry_is_preserved_and_missing_index_is_added(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table): void {
            $table->dropIndex(['expires_at']);
        });
        $user = \App\Models\User::factory()->create();
        $token = $user->createToken('already-patched', ['*'], now()->addDay())->accessToken;
        $before = DB::table('personal_access_tokens')->where('id', $token->id)->first();
        $migration = require database_path('migrations/2026_02_04_114039_add_expires_at_to_personal_access_tokens_table.php');
        $migration->up();
        $migration->up();
        $this->assertEquals($before, DB::table('personal_access_tokens')->where('id', $token->id)->first());
        $this->assertTrue(Schema::hasIndex('personal_access_tokens', ['expires_at']));
    }
}
