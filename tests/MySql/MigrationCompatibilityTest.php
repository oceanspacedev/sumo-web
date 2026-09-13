<?php

namespace Tests\MySql;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\MySqlTestCase;

class MigrationCompatibilityTest extends MySqlTestCase
{
    public function test_new_install_can_seed_login_and_render_dashboard(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $this->artisan('db:seed', ['--force' => true])->assertSuccessful();
        $this->post('/postlogin', ['username' => 'admin', 'password' => 'complete123'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('username', 'admin')->firstOrFail());
        $this->get('/dashboard')->assertOk();
    }

    protected function tearDown(): void
    {
        try {
            // Leave the disposable database empty for other integration tests.
            $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        } finally {
            parent::tearDown();
        }
    }

    public function test_fresh_install_and_legacy_upgrade_preserve_business_rows_and_tokens(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $this->assertTrue(Schema::hasIndex('personal_access_tokens', ['expires_at']));
        $this->assertTrue(Schema::hasColumn('requests', 'audit_notes'));
        $this->assertCount(count(glob(database_path('migrations/*.php'))), DB::table('migrations')->get());

        // Roll back only the additive upgrade, leaving the actual historical
        // migrations as the legacy schema. Then add representative old data.
        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasColumn('personal_access_tokens', 'expires_at'));
        $user = User::factory()->create(['username' => 'legacy-user']);
        DB::table('products')->insert([
            'id' => 42, 'product' => 'LEGACY PRODUCT', 'category_id' => 1,
            'unit_type_id' => 1, 'stock' => 19.5, 'price' => 2500,
        ]);
        DB::table('requests')->insert([
            'id' => 83, 'request_code' => 'REQ83', 'user_id' => $user->id,
            'date' => '2024-04-02 08:30:00', 'total_cost' => 10000,
            'request_type_id' => 1, 'status_client' => 3,
            'request_file' => 'legacy.pdf', 'audit_notes' => 'preserved',
        ]);
        DB::table('request_details')->insert([
            'request_id' => 83, 'product_id' => 42, 'qty_request' => 4, 'qty_approved' => 0,
        ]);
        $plain = 'legacy-mysql-bearer';
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class, 'tokenable_id' => $user->id,
            'name' => 'authToken', 'token' => hash('sha256', $plain), 'abilities' => '["*"]',
            'created_at' => '2024-04-02 08:30:00', 'updated_at' => '2024-04-02 08:30:00',
        ]);
        $snapshots = [];
        foreach (['users', 'products', 'requests', 'request_details'] as $table) {
            $snapshots[$table] = DB::table($table)->get()->toJson();
        }
        $oldToken = (array) DB::table('personal_access_tokens')->first();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        foreach ($snapshots as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->get()->toJson(), $table.' changed during upgrade');
        }
        $newToken = (array) DB::table('personal_access_tokens')->first();
        $this->assertNull($newToken['expires_at']);
        unset($newToken['expires_at']);
        $this->assertSame($oldToken, $newToken);
        $this->assertTrue(Schema::hasIndex('personal_access_tokens', ['expires_at']));
        $this->withToken($tokenId.'|'.$plain)->getJson('/api/user')->assertOk()->assertJsonPath('data.id', $user->id);

        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        $this->assertSame($snapshots['requests'], DB::table('requests')->get()->toJson());
        $this->assertSame(hash('sha256', $plain), DB::table('personal_access_tokens')->value('token'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }
}
