<?php

namespace Tests\Concerns;

use App\Helpers\ResponseFormatter;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

trait ExercisesUserProductCrud
{
    use RefreshDatabase;

    private string $crudStorage;
    private string $originalStorage;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalStorage = $this->app->storagePath();
        $this->crudStorage = sys_get_temp_dir().'/web-gais-crud-'.bin2hex(random_bytes(8));
        // The legacy product deletion uses storage_path directly. Give both
        // it and the filesystem disk the same disposable root for a real test.
        $this->app->useStoragePath($this->crudStorage);
        config(['filesystems.disks.public.root' => $this->crudStorage.'/app/public']);
        Storage::forgetDisk('public');
        DB::table('areas')->insert(['id' => 1, 'area' => 'CRUD AREA']);
        DB::table('divisions')->insert(['id' => 1, 'division' => 'CRUD DIVISION', 'area_id' => 1]);
        DB::table('badan_usahas')->insert(['id' => 1, 'badan_usaha' => 'CRUD COMPANY']);
        DB::table('roles')->insert(['id' => 1, 'role' => 'ADMIN']);
        DB::table('roles')->insert(['id' => 4, 'role' => 'USER']);
        DB::table('categories')->insert(['id' => 1, 'category' => 'CRUD CATEGORY']);
        DB::table('unit_types')->insert(['id' => 1, 'unit_type' => 'PCS']);
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
        (new \ReflectionProperty(ResponseFormatter::class, 'response'))->setValue(null, [
            'meta' => ['code' => 200, 'status' => 'success', 'message' => null], 'data' => null,
        ]);
    }

    protected function tearDown(): void
    {
        try {
            $this->app->useStoragePath($this->originalStorage);
            File::deleteDirectory($this->crudStorage);
        } finally {
            parent::tearDown();
        }
    }

    public function test_user_create_read_update_photo_delete_and_restore(): void
    {
        $payload = $this->userPayload();
        $this->post('/user/create', $payload + ['profile_picture' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertRedirect('/user')->assertSessionHas('success')->assertSessionMissing('error');
        $user = User::where('username', 'crud.person')->firstOrFail();
        $this->assertTrue(Hash::check('crud-test-password', $user->password));
        $this->assertSame(60, strlen($user->remember_token));
        Storage::disk('public')->assertExists('profile/'.$user->profile_picture);
        $originalPhoto = $user->profile_picture;
        $this->get('/user?search=CRUD PERSON')->assertOk()->assertSee('CRUD PERSON');
        $this->get('/user/'.$user->id.'/edit')->assertOk()->assertSee('crud.person');
        $this->get('/user/'.$user->id.'/profile')->assertOk();

        $payload['fullname'] = 'CRUD UPDATED';
        $payload['password'] = 'updated-test-password';
        $this->post('/user/'.$user->id.'/update', $payload)
            ->assertRedirect('/user')->assertSessionHas('success')->assertSessionMissing('error');
        $user->refresh();
        $this->assertSame('CRUD UPDATED', $user->fullname);
        $this->assertTrue(Hash::check('updated-test-password', $user->password));
        $this->assertSame($originalPhoto, $user->profile_picture);
        $this->post('/user/'.$user->id.'/update', $payload + ['profile_picture' => UploadedFile::fake()->image('new.png')])
            ->assertSessionHas('success')->assertSessionMissing('error');
        Storage::disk('public')->assertExists('profile/'.$user->fresh()->profile_picture);
        $this->assertNotSame($originalPhoto, $user->fresh()->profile_picture);

        $this->get('/user/'.$user->id.'/delete')->assertRedirect('/user')->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->get('/user/'.$user->id.'/edit')->assertNotFound();
        $this->get('/user/'.$user->id.'/active')->assertRedirect('/user')->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
        $this->get('/user/'.$user->id.'/edit')->assertOk();
    }

    public function test_product_create_read_update_and_delete_image(): void
    {
        $payload = ['product' => 'CRUD PRODUCT', 'category_id' => 1, 'unit_type_id' => 1,
            'price' => 1250.5, 'stock' => 12.5, 'description' => 'Initial description'];
        $this->post('/product/create', $payload + ['product_image' => UploadedFile::fake()->image('item.jpg')])
            ->assertRedirect('/product')->assertSessionHas('success')->assertSessionMissing('error');
        $product = Product::where('product', 'CRUD PRODUCT')->firstOrFail();
        $product->forceFill(['updated_at' => '2026-09-13 08:00:00'])->save();
        $originalPhoto = $product->product_image;
        Storage::disk('public')->assertExists('product/'.$originalPhoto);
        $this->get('/product?search=CRUD PRODUCT')->assertOk()->assertSee('CRUD PRODUCT')->assertSee('Sunday, 13 Sep 2026');
        $this->get('/product/'.$product->id.'/edit')->assertOk()->assertSee('Initial description');
        $this->getJson('/product/get?req_type_id=1')->assertOk()->assertJsonPath('0.id', $product->id);

        $payload['product'] = 'CRUD PRODUCT UPDATED';
        $payload['price'] = 0;
        $payload['stock'] = 0;
        $this->post('/product/'.$product->id.'/update', $payload)
            ->assertRedirect('/product')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 0, 'stock' => 0]);
        $this->assertSame($originalPhoto, $product->fresh()->product_image);
        $this->post('/product/'.$product->id.'/update', $payload + ['product_image' => UploadedFile::fake()->image('new.png')])
            ->assertSessionHas('success')->assertSessionMissing('error');
        $currentPhoto = $product->fresh()->product_image;
        Storage::disk('public')->assertExists('product/'.$currentPhoto);
        $this->get('/product/'.$product->id.'/delete')->assertRedirect('/product')->assertSessionHas('success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
        Storage::disk('public')->assertMissing('product/'.$currentPhoto);
        $this->getJson('/product/get?req_type_id=1')->assertExactJson([]);
        $this->get('/product/'.$product->id.'/edit')->assertNotFound();
    }

    public function test_product_without_optional_photo_can_be_created_and_deleted(): void
    {
        $this->post('/product/create', ['product' => 'NO PHOTO', 'category_id' => 1, 'unit_type_id' => 1])
            ->assertSessionHas('success')->assertSessionMissing('error');
        $product = Product::where('product', 'NO PHOTO')->firstOrFail();
        $this->assertNull($product->product_image);
        $this->get('/product/'.$product->id.'/delete')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_api_unit_type_create_read_update_delete_with_bearer_auth(): void
    {
        $token = $this->admin->createToken('crud-test')->plainTextToken;
        auth()->forgetGuards();
        $response = $this->withToken($token)->postJson('/api/unittype', ['unit_type' => 'CRUD UNIT'])
            ->assertOk()->assertJsonPath('meta.status', 'success')->assertJsonPath('data.unit_type', 'CRUD UNIT');
        $id = $response->json('data.id');
        $this->getJson('/api/unittype')->assertOk()->assertJsonFragment(['id' => $id, 'unit_type' => 'CRUD UNIT']);
        $this->patchJson('/api/unittype/'.$id, ['unit_type' => 'UPDATED UNIT'])
            ->assertOk()->assertJsonPath('data.unit_type', 'UPDATED UNIT');
        $this->assertDatabaseHas('unit_types', ['id' => $id, 'unit_type' => 'UPDATED UNIT']);
        $this->deleteJson('/api/unittype/'.$id)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSoftDeleted('unit_types', ['id' => $id]);
        $this->getJson('/api/unittype')->assertOk()->assertJsonMissing(['unit_type' => 'UPDATED UNIT']);
    }

    public function test_user_create_without_optional_photo_succeeds(): void
    {
        $this->post('/user/create', $this->userPayload())
            ->assertRedirect('/user')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseHas('users', ['username' => 'crud.person', 'profile_picture' => null]);
        $user = User::where('username', 'crud.person')->sole();
        $this->assertTrue(Hash::check('crud-test-password', $user->password));
        $this->assertSame(60, strlen($user->remember_token));
        $this->assertSame(asset('images/default.png'), $user->getProfilePic());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_user_create_rejects_invalid_photo_without_inserting_a_partial_user(): void
    {
        foreach ([
            UploadedFile::fake()->create('not-an-image.txt', 1, 'text/plain'),
            UploadedFile::fake()->image('too-large.jpg')->size(2049),
        ] as $photo) {
            $this->post('/user/create', $this->userPayload() + ['profile_picture' => $photo])
                ->assertRedirect('/user')->assertSessionHas('error')->assertSessionMissing('success');
            $this->assertDatabaseMissing('users', ['username' => 'crud.person']);
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_user_create_storage_failure_does_not_insert_a_partial_user(): void
    {
        Storage::shouldReceive('disk')->with('public')->andReturnSelf();
        Storage::shouldReceive('exists')->with('profile')->andReturnTrue();
        Storage::shouldReceive('exists')->with(\Mockery::pattern('/^profile\\/Profile - /'))->andReturnFalse();
        Storage::shouldReceive('putFileAs')->once()->andReturnFalse();

        $this->post('/user/create', $this->userPayload() + ['profile_picture' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertRedirect('/user')->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertDatabaseMissing('users', ['username' => 'crud.person']);
    }

    public function test_user_create_database_failure_does_not_leave_an_uploaded_photo(): void
    {
        $existing = User::factory()->create(['username' => 'crud.person', 'fullname' => 'EXISTING USER']);
        $this->post('/user/create', $this->userPayload() + ['profile_picture' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertRedirect('/user')->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertSame($existing->id, User::where('username', 'crud.person')->sole()->id);
        $this->assertSame('EXISTING USER', $existing->fresh()->fullname);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    private function userPayload(): array
    {
        return ['fullname' => 'CRUD PERSON', 'username' => 'crud.person', 'password' => 'crud-test-password',
            'badan_usaha_id' => 1, 'division_id' => 1, 'role_id' => 4, 'approval_id' => $this->admin->id];
    }
}
