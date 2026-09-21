<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\Rent;
use App\Models\User;
use App\Models\WaNotificationSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'areas' => ['area' => 'JAKARTA'],
            'badan_usahas' => ['badan_usaha' => 'COMPANY'],
            'divisions' => ['division' => 'GENERAL', 'area_id' => 1],
            'roles' => ['role' => 'ADMIN'],
        ] as $table => $values) {
            DB::table($table)->insert(['id' => 1] + $values);
        }

        $this->adminUser = User::create([
            'username' => 'admin_test',
            'fullname' => 'Administrator Test',
            'password' => Hash::make('secret'),
            'badan_usaha_id' => 1,
            'division_id' => 1,
            'role_id' => 1,
            'approval_id' => 1,
        ]);
    }

    public function test_admin_can_access_notification_settings_page(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/notification-settings');
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Notifikasi WhatsApp');
    }

    public function test_admin_can_update_notification_settings(): void
    {
        $payload = [
            'is_enabled' => '1',
            'target_phones' => '081234567890, 089876543210',
            'send_time' => '09:00',
            'remind_rent' => '1',
            'rent_days_before' => '30,14,7,1',
            'remind_insurance' => '1',
            'insurance_days_before' => '30,14,7,1',
            'remind_request_cutoff' => '1',
            'request_cutoff_days_before' => '3,1',
        ];

        $response = $this->actingAs($this->adminUser)->post('/notification-settings/update', $payload);
        $response->assertRedirect('/notification-settings');

        $setting = WaNotificationSetting::getSettings();
        $this->assertTrue($setting->is_enabled);
        $this->assertSame('081234567890, 089876543210', $setting->target_phones);
        $this->assertSame('09:00', $setting->send_time);
        $this->assertCount(2, $setting->phone_list);
    }

    public function test_admin_can_send_test_message_with_mocked_gateway(): void
    {
        Http::fake([
            'https://waghub.mekayastudio.com/*' => Http::response([
                'status' => 'success',
                'message' => 'Message queued successfully',
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser)->post('/notification-settings/test', [
            'test_phone' => '081234567890',
        ]);

        $response->assertRedirect('/notification-settings');
        $response->assertSessionHas('success');
    }

    public function test_reminder_artisan_command_runs_successfully(): void
    {
        $this->artisan('reminder:whatsapp --dry-run')
            ->expectsOutputToContain('Memulai pemeriksaan jatuh tempo')
            ->assertExitCode(0);
    }

    public function test_reminder_sends_to_each_records_phones_and_falls_back_to_global(): void
    {
        config([
            'services.waghub.token' => 'test-token',
            'services.waghub.url' => 'https://waghub.mekayastudio.com/api/v1/messages',
        ]);

        Http::fake([
            'https://waghub.mekayastudio.com/*' => Http::response(['status' => 'success'], 200),
        ]);

        WaNotificationSetting::getSettings()->update([
            'is_enabled' => true,
            'target_phones' => '081111111111',
            'remind_rent' => true,
            'remind_insurance' => true,
            'remind_request_cutoff' => false,
        ]);

        $today = now()->toDateString();

        Rent::create([
            'rent_code' => 'RENT-OWN',
            'rented_detail' => 'Gudang A',
            'first_party' => 'PT A',
            'second_party' => 'PT B',
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => '082222222222',
        ]);

        Rent::create([
            'rent_code' => 'RENT-FALLBACK',
            'rented_detail' => 'Gudang B',
            'first_party' => 'PT A',
            'second_party' => 'PT C',
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => null,
        ]);

        DB::table('insurance_categories')->insert(['id' => 1, 'insurance_category' => 'ALL RISK']);
        DB::table('insurance_scopes')->insert(['id' => 1, 'insurance_scope' => 'STOCK']);

        Insurance::create([
            'policy_number' => 'POL-OWN',
            'insured_name' => 'PT D',
            'insured_detail' => 'Gudang D',
            'insurance_category_id' => 1,
            'insurance_scope_id' => 1,
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => '083333333333, 084444444444',
        ]);

        $this->artisan('reminder:whatsapp')->assertExitCode(0);

        $recipients = collect(Http::recorded())
            ->map(fn ($pair) => $pair[0]->data()['recipient']['value'])
            ->all();

        $this->assertEqualsCanonicalizing([
            '082222222222',
            '081111111111',
            '083333333333',
            '084444444444',
        ], $recipients);
    }

    public function test_rent_and_insurance_forms_save_reminder_phones(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/rent')
            ->assertOk()
            ->assertSee('Nomor WhatsApp Pengingat');

        $this->actingAs($this->adminUser)->post('/rent/store', [
            'rented_address' => 'Jl A',
            'rented_detail' => 'Gudang Tes',
            'first_party' => 'PT A',
            'second_party' => 'PT B',
            'rent_per_year' => 1000,
            'join_date' => '01/01/2026',
            'expired_date' => '21/09/2026',
            'status' => 'BERJALAN',
            'reminder_phones' => '085555555555, 086666666666',
        ])->assertRedirect('rent');

        $rent = Rent::where('rented_detail', 'Gudang Tes')->first();
        $this->assertNotNull($rent);
        $this->assertSame('085555555555, 086666666666', $rent->reminder_phones);

        $this->actingAs($this->adminUser)
            ->get('/rent/'.$rent->id.'/edit')
            ->assertOk()
            ->assertSee('085555555555, 086666666666');

        DB::table('insurance_categories')->insert(['id' => 1, 'insurance_category' => 'ALL RISK']);
        DB::table('insurance_scopes')->insert(['id' => 1, 'insurance_scope' => 'STOCK']);

        $this->actingAs($this->adminUser)
            ->get('/insurance')
            ->assertOk()
            ->assertSee('Nomor WhatsApp Pengingat');

        $this->actingAs($this->adminUser)->post('/insurance/store', [
            'policy_number' => 'POL-FORM',
            'insured_name' => 'PT D',
            'insured_detail' => 'Gudang D',
            'risk_address' => 'Jl D',
            'insurance_category_id' => 1,
            'insurance_scope_id' => 1,
            'join_date' => '01/01/2026',
            'expired_date' => '21/09/2026',
            'status' => 'BERJALAN',
            'reminder_phones' => '087777777777',
        ])->assertRedirect('insurance');

        $insurance = Insurance::where('policy_number', 'POL-FORM')->first();
        $this->assertNotNull($insurance);
        $this->assertSame('087777777777', $insurance->reminder_phones);

        $this->actingAs($this->adminUser)
            ->get('/insurance/'.$insurance->id.'/edit')
            ->assertOk()
            ->assertSee('087777777777');
    }

    public function test_bulk_replace_swaps_one_phone_on_every_matching_record(): void
    {
        $today = now()->toDateString();

        $rentOwn = Rent::create([
            'rent_code' => 'RENT-KAKA',
            'rented_detail' => 'Gudang Kaka',
            'first_party' => 'PT A',
            'second_party' => 'PT B',
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => '0812-1111-1111, 083333333333',
        ]);

        $rentOther = Rent::create([
            'rent_code' => 'RENT-LAIN',
            'rented_detail' => 'Gudang Lain',
            'first_party' => 'PT A',
            'second_party' => 'PT C',
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => '089999999999',
        ]);

        $rentEmpty = Rent::create([
            'rent_code' => 'RENT-KOSONG',
            'rented_detail' => 'Gudang Kosong',
            'first_party' => 'PT A',
            'second_party' => 'PT E',
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => null,
        ]);

        DB::table('insurance_categories')->insert(['id' => 1, 'insurance_category' => 'ALL RISK']);
        DB::table('insurance_scopes')->insert(['id' => 1, 'insurance_scope' => 'STOCK']);

        $insurance = Insurance::create([
            'policy_number' => 'POL-KAKA',
            'insured_name' => 'PT D',
            'insured_detail' => 'Gudang D',
            'insurance_category_id' => 1,
            'insurance_scope_id' => 1,
            'join_date' => $today,
            'expired_date' => $today,
            'status' => 'BERJALAN',
            'user_id' => $this->adminUser->id,
            'reminder_phones' => '081211111111',
        ]);

        $this->actingAs($this->adminUser)
            ->get('/notification-settings')
            ->assertOk()
            ->assertSee('Ganti Nomor Secara Masal')
            ->assertSee('Cari nomor atau nama data')
            ->assertSee('081211111111')
            ->assertSee('RENT-KAKA')
            ->assertSee('Gudang Kaka')
            ->assertSee('POL-KAKA')
            ->assertSee('089999999999')
            ->assertDontSee('Gudang Kosong');

        $replace = $this->actingAs($this->adminUser)->followingRedirects()->post('/notification-settings/replace-phones', [
            'action' => 'replace',
            'old_phone' => '081211111111',
            'new_phone' => '082222222222',
        ]);

        $replace->assertOk();
        $replace->assertSee('1 perjanjian sewa');
        $replace->assertSee('1 polis asuransi');
        $this->assertSame('082222222222, 083333333333', $rentOwn->fresh()->reminder_phones);
        $this->assertSame('082222222222', $insurance->fresh()->reminder_phones);
        $this->assertSame('089999999999', $rentOther->fresh()->reminder_phones);
        $this->assertNull($rentEmpty->fresh()->reminder_phones);

        $delete = $this->actingAs($this->adminUser)->followingRedirects()->post('/notification-settings/replace-phones', [
            'action' => 'delete',
            'old_phone' => '089999999999',
        ]);

        $delete->assertOk();
        $delete->assertSee('1 perjanjian sewa');
        $this->assertNull($rentOther->fresh()->reminder_phones);
        $this->assertSame('082222222222, 083333333333', $rentOwn->fresh()->reminder_phones);
    }
}
