<?php

namespace Tests\Feature;

use App\Helpers\ResponseFormatter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The legacy formatter is static; each production PHP request starts
        // with these defaults, while PHPUnit shares its process across tests.
        (new \ReflectionProperty(ResponseFormatter::class, 'response'))->setValue(null, [
            'meta' => ['code' => 200, 'status' => 'success', 'message' => null],
            'data' => null,
        ]);
    }

    public function test_web_login_logout_and_invalid_credentials_keep_redirects(): void
    {
        $user = User::factory()->create();
        $this->post('/postlogin', ['username' => $user->username, 'password' => 'wrong'])
            ->assertRedirect('/login')->assertSessionHasErrors('login_error');
        $this->assertGuest();
        $this->post('/postlogin', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->get('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_api_login_response_and_new_bearer_token_keep_contract(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/login', ['username' => $user->username, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('meta.status', 'success')
            ->assertJsonPath('meta.message', 'Authenticated')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.username', $user->username)
            ->assertJsonMissingPath('data.user.password');
        $token = $response->json('data.access_token');
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'expires_at' => null]);
        Auth::forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_invalid_api_credentials_preserve_legacy_error_envelope(): void
    {
        $this->postJson('/api/login', ['username' => 'missing', 'password' => 'incorrect'])
            ->assertStatus(500)->assertJsonPath('meta.status', 'error')
            ->assertJsonPath('data.message', 'Unauthorized');
    }

    public function test_legacy_token_without_expiry_survives_additive_migration(): void
    {
        $user = User::factory()->create();
        $migration = require database_path('migrations/2026_02_04_114039_add_expires_at_to_personal_access_tokens_table.php');
        $migration->down();
        $plain = 'legacy-token-secret';
        $id = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class, 'tokenable_id' => $user->id,
            'name' => 'authToken', 'token' => hash('sha256', $plain),
            'abilities' => '["*"]', 'created_at' => now()->subYear(), 'updated_at' => now()->subYear(),
        ]);
        $migration->up();
        $this->withToken($id.'|'.$plain)->getJson('/api/user')
            ->assertOk()->assertJsonPath('data.id', $user->id);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $id, 'expires_at' => null]);
    }

    public function test_unexpired_token_works_and_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $valid = $user->createToken('valid', ['*'], now()->addMinute());
        $expired = $user->createToken('expired', ['*'], now()->subMinute());
        $this->withToken($valid->plainTextToken)->getJson('/api/user')->assertOk();
        Auth::forgetGuards();
        $this->withToken($expired->plainTextToken)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_guest_role_and_division_access_contracts_are_retained(): void
    {
        $this->get('/category')->assertRedirect('/login');
        $this->getJson('/api/user')->assertUnauthorized();
        $user = User::factory()->create(['role_id' => 4, 'division_id' => 1]);
        $this->actingAs($user)->get('/category')->assertRedirect('/');
        $this->actingAs($user)->get('/insurance')->assertRedirect('/');
        $user->update(['role_id' => 3, 'division_id' => 6]);
        $this->actingAs($user)->get('/insurance')->assertOk();
        $this->get('/insurance/export/template')->assertOk();
        $user->update(['division_id' => 5]);
        $this->actingAs($user)->get('/insurance')->assertForbidden();
        $user->update(['role_id' => 1]);
        $this->actingAs($user)->get('/category')->assertOk();
    }

    public function test_special_insurance_reader_can_view_but_cannot_manage(): void
    {
        $reader = User::factory()->create(['id' => 75, 'role_id' => 3, 'division_id' => 1]);
        $this->actingAs($reader)->get('/insurance')->assertOk();
        $this->post('/insurance/store', [])->assertForbidden();
        $this->get('/insurance/export/template')->assertForbidden();
    }

    public function test_csrf_is_enforced_outside_the_test_environment(): void
    {
        // Laravel normally skips CSRF in PHPUnit. Switch only the runtime
        // environment after the isolated database has been bootstrapped.
        $this->app->instance('env', 'local');
        $this->post('/postlogin', [])->assertStatus(419);
        $this->withSession(['_token' => 'csrf-test-token'])
            ->post('/postlogin', ['_token' => 'csrf-test-token'])->assertRedirect('/login');
    }

    public function test_api_limiter_accepts_sixty_requests_then_returns_429(): void
    {
        Route::middleware('api')->get('/api/upgrade-throttle-probe', fn () => response()->json(['ok' => true]));
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/upgrade-throttle-probe')->assertOk();
        }
        $this->getJson('/api/upgrade-throttle-probe')->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_builtin_cors_and_untrusted_proxy_headers_preserve_behavior(): void
    {
        $this->options('/api/login', [], [
            'Origin' => 'https://example.test', 'Access-Control-Request-Method' => 'POST',
        ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', '*');
        Route::get('/upgrade-proxy-probe', fn (\Illuminate\Http\Request $request) => response()->json([
            'host' => $request->getHost(), 'secure' => $request->isSecure(), 'ip' => $request->ip(),
        ]));
        $this->getJson('/upgrade-proxy-probe', [
            'X-Forwarded-Host' => 'untrusted.example', 'X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.10',
        ])->assertOk()->assertJson(['host' => 'localhost', 'secure' => false, 'ip' => '127.0.0.1']);
    }

    public function test_public_scanner_lookups_keep_product_and_request_json_contracts(): void
    {
        $user = User::factory()->create();
        DB::table('unit_types')->insert(['id' => 1, 'unit_type' => 'PCS']);
        DB::table('categories')->insert(['id' => 1, 'category' => 'TEST']);
        DB::table('products')->insert([
            'id' => 42, 'product' => 'SCANNED PRODUCT', 'unit_type_id' => 1, 'category_id' => 1, 'price' => 2500,
        ]);
        DB::table('requests')->insert([
            'id' => 55, 'request_code' => 'REQ55', 'user_id' => $user->id,
            'date' => '2026-09-13 10:00:00', 'total_cost' => 5000, 'request_type_id' => 1,
        ]);
        DB::table('request_details')->insert([
            'id' => 77, 'request_id' => 55, 'product_id' => 42, 'qty_request' => 2,
        ]);
        $this->postJson('/search-product', ['id' => '42'])->assertOk()
            ->assertJsonPath('0.product', 'SCANNED PRODUCT')
            ->assertJsonPath('0.unit_type.unit_type', 'PCS')
            ->assertJsonPath('0.category.category', 'TEST');
        $this->postJson('/search', ['id' => '77'])->assertOk()
            ->assertJsonPath('0.id', 55)
            ->assertJsonPath('0.request_code', 'REQ55')
            ->assertJsonPath('0.request_detail.0.product.product', 'SCANNED PRODUCT');
        $this->postJson('/search', ['id' => '99999'])->assertOk()->assertExactJson([]);
        $this->postJson('/search-product', ['id' => '99999'])->assertOk()->assertExactJson([]);
    }
}
