<?php

namespace Tests\Concerns;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\Rent;
use App\Models\RentUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

trait ExercisesContractCrud
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        foreach ([
            'areas' => ['area' => 'JAKARTA'],
            'badan_usahas' => ['badan_usaha' => 'COMPANY'],
            'divisions' => ['division' => 'GENERAL', 'area_id' => 1],
            'roles' => ['role' => 'ADMIN'],
            'insurance_providers' => ['insurance_provider' => 'PROVIDER'],
            'insurance_categories' => ['insurance_category' => 'PROPERTY'],
            'insurance_scopes' => ['insurance_scope' => 'ALLRISK'],
        ] as $table => $values) {
            DB::table($table)->insert(['id' => 1] + $values);
        }

        $this->actingAs(User::factory()->create(['username' => 'contract-admin', 'fullname' => 'CONTRACT ADMIN', 'role_id' => 1]));
    }

    public function test_insurance_can_be_created_read_edited_updated_and_soft_deleted_over_http(): void
    {
        $this->get('/insurance')->assertOk()->assertSee('action="/insurance/store"', false);
        $this->post('/insurance/store', $this->insurancePayload())
            ->assertRedirect('/insurance')->assertSessionHas('success')->assertSessionMissing('error');

        $insurance = Insurance::sole();
        $this->assertDatabaseHas('insurances', [
            'id' => $insurance->id, 'policy_number' => 'CRUD/POLICY-001',
            'insured_name' => 'INSURED COMPANY', 'warehouse_code' => 'WH-01',
            'stock_worth' => 12000000, 'actual_stock_worth' => 10000000,
            'building_worth' => 250000000, 'stock_premium' => 125000, 'building_premium' => 250000,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31',
            'insurance_category_id' => 1, 'insurance_scope_id' => 1, 'stock_inprov_id' => 1,
            'user_id' => auth()->id(),
        ]);
        $this->get('/insurance')->assertOk()->assertSee('CRUD/POLICY-001');
        $this->get('/insurance/'.$insurance->id)->assertOk()->assertSee('CRUD/POLICY-001');
        $this->get('/insurance/'.$insurance->id.'/edit')->assertOk()
            ->assertSee('action="/insurance/'.$insurance->id.'/update"', false)
            ->assertSee('value="12000000"', false);

        $updated = array_replace($this->insurancePayload(), [
            'policy_number' => 'CRUD/POLICY-REVISED', 'insured_name' => 'UPDATED INSURED',
            'stock_worth' => 24000000, 'actual_stock_worth' => 0, 'stock_premium' => 0,
            'join_date' => '15/02/2026', 'expired_date' => '14/02/2027', 'notes' => 'Revised insurance',
        ]);
        $this->post('/insurance/'.$insurance->id.'/update', $updated)
            ->assertRedirect('/insurance')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('insurances', 1);
        $this->assertDatabaseHas('insurances', [
            'id' => $insurance->id, 'policy_number' => 'CRUD/POLICY-REVISED',
            'insured_name' => 'UPDATED INSURED', 'stock_worth' => 24000000,
            'actual_stock_worth' => 0, 'stock_premium' => 0, 'building_worth' => 250000000,
            'join_date' => '2026-02-15', 'expired_date' => '2027-02-14', 'notes' => 'Revised insurance',
        ]);
        $this->get('/insurance/'.$insurance->id)->assertOk()->assertSee('CRUD/POLICY-REVISED');

        $this->get('/insurance/'.$insurance->id.'/delete')
            ->assertRedirect('/insurance')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted('insurances', ['id' => $insurance->id]);
        $this->assertSame(0, Insurance::count());
        $this->get('/insurance')->assertOk()->assertDontSee('CRUD/POLICY-REVISED');
        $this->get('/insurance/'.$insurance->id)->assertNotFound();
        $this->get('/insurance/'.$insurance->id.'/edit')->assertNotFound();
    }

    public function test_insurance_extension_crud_keeps_parent_relationship_and_amounts(): void
    {
        $this->post('/insurance/store', $this->insurancePayload())
            ->assertSessionHas('success')->assertSessionMissing('error');
        $parent = Insurance::sole();
        $parentPath = '/insurance/'.$parent->id;
        $this->get($parentPath)->assertOk()->assertSee('action="/insurance/storeUpdate"', false);

        $payload = $this->insuranceFinancialPayload() + ['insurance_id' => $parent->id, 'policy_number' => 'CRUD/EXTENSION-001'];
        $this->post('/insurance/storeUpdate', $payload)
            ->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $extension = InsuranceUpdate::sole();
        $this->assertTrue($extension->insurance->is($parent));
        $this->assertSame(1, $parent->fresh()->insurance_update->count());
        $this->assertDatabaseHas('insurance_updates', [
            'id' => $extension->id, 'insurance_id' => $parent->id, 'stock_worth' => 12000000,
            'actual_stock_worth' => 10000000, 'building_worth' => 250000000,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31', 'user_id' => auth()->id(),
        ]);
        $this->get('/insurance')->assertOk()->assertSee('CRUD/EXTENSION-001');
        $this->get($parentPath)->assertOk()->assertSee('CRUD/EXTENSION-001')->assertSee('12.000.000');
        $this->get('/insurance/'.$extension->id.'/'.$parent->id.'/editUpdate')->assertOk()
            ->assertSee('action="/insurance/'.$extension->id.'/updateInsuranceUpdate"', false)
            ->assertSee('value="12000000"', false);

        $this->post('/insurance/'.$extension->id.'/updateInsuranceUpdate', array_replace($payload, [
            'policy_number' => 'CRUD/EXTENSION-REVISED', 'actual_stock_worth' => 0,
            'building_worth' => 300000000, 'building_premium' => 0,
            'join_date' => '01/01/2027', 'expired_date' => '31/12/2027', 'notes' => 'Revised extension',
        ]))->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('insurance_updates', 1);
        $this->assertDatabaseHas('insurance_updates', [
            'id' => $extension->id, 'insurance_id' => $parent->id, 'policy_number' => 'CRUD/EXTENSION-REVISED',
            'stock_worth' => 12000000, 'actual_stock_worth' => 0, 'building_worth' => 300000000, 'building_premium' => 0,
            'join_date' => '2027-01-01', 'expired_date' => '2027-12-31', 'notes' => 'Revised extension',
        ]);
        $this->assertSame(250000000, $parent->fresh()->building_worth);
        $this->get($parentPath)->assertOk()->assertSee('CRUD/EXTENSION-REVISED')->assertSee('300.000.000');

        $this->get('/insurance/'.$extension->id.'/deleteUpdate/'.$parent->id)
            ->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted('insurance_updates', ['id' => $extension->id]);
        $this->assertSame(0, $parent->fresh()->insurance_update->count());
        $this->assertNotSoftDeleted('insurances', ['id' => $parent->id]);
        $this->get($parentPath)->assertOk()->assertDontSee('CRUD/EXTENSION-REVISED');
        $this->get('/insurance/'.$extension->id.'/'.$parent->id.'/editUpdate')->assertNotFound();
    }

    public function test_rent_can_be_created_read_edited_updated_and_soft_deleted_with_payment_file_retained(): void
    {
        $this->get('/rent')->assertOk()->assertSee('action="/rent/store"', false);
        $this->post('/rent/store', $this->rentPayload() + [
            'payment_evidence_file' => UploadedFile::fake()->image('payment.png'),
        ])->assertRedirect('/rent')->assertSessionHas('success')->assertSessionMissing('error');
        $rent = Rent::sole();
        $paymentFile = $rent->payment_evidence_file;
        Storage::disk('public')->assertExists('Rent_File/'.$paymentFile);
        $this->assertDatabaseHas('rents', [
            'id' => $rent->id, 'rent_code' => 'RENT0001', 'rented_detail' => 'CRUD BUILDING',
            'rent_per_year' => 12000000, 'cvcs_fund' => 1500000, 'online_fund' => 500000,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31',
            'month_before_reminder' => '2', 'user_id' => auth()->id(),
        ]);
        $this->get('/rent')->assertOk()->assertSee('CRUD BUILDING');
        $this->get('/rent/'.$rent->id)->assertOk()->assertSee('CRUD BUILDING')->assertSee('12.000.000');
        $this->get('/rent/'.$rent->id.'/edit')->assertOk()
            ->assertSee('action="/rent/'.$rent->id.'/update"', false)
            ->assertSee('value="12000000"', false);

        $this->post('/rent/'.$rent->id.'/update', array_replace($this->rentPayload(), [
            'rent_id' => $rent->id, 'rented_detail' => 'CRUD BUILDING REVISED', 'rent_per_year' => 24000000,
            'online_fund' => 0, 'join_date' => '15/02/2026', 'expired_date' => '14/02/2027',
            'notes' => 'Revised rent',
        ]))->assertRedirect('/rent/'.$rent->id)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('rents', 1);
        $this->assertDatabaseHas('rents', [
            'id' => $rent->id, 'rent_code' => 'RENT0001', 'rented_detail' => 'CRUD BUILDING REVISED',
            'rent_per_year' => 24000000, 'cvcs_fund' => 1500000, 'online_fund' => 0,
            'join_date' => '2026-02-15', 'expired_date' => '2027-02-14',
            'notes' => 'Revised rent', 'payment_evidence_file' => $paymentFile,
        ]);
        Storage::disk('public')->assertExists('Rent_File/'.$rent->fresh()->payment_evidence_file);
        $this->get('/rent/'.$rent->id)->assertOk()->assertSee('CRUD BUILDING REVISED')->assertSee('24.000.000');

        $this->get('/rent/'.$rent->id.'/delete')
            ->assertRedirect('/rent')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted('rents', ['id' => $rent->id]);
        $this->assertSame(0, Rent::count());
        $this->get('/rent')->assertOk()->assertDontSee('CRUD BUILDING REVISED');
        $this->get('/rent/'.$rent->id)->assertNotFound();
        $this->get('/rent/'.$rent->id.'/edit')->assertNotFound();
    }

    public function test_rent_extension_crud_keeps_parent_amounts_and_replaces_payment_upload(): void
    {
        $this->post('/rent/store', $this->rentPayload())
            ->assertSessionHas('success')->assertSessionMissing('error');
        $parent = Rent::sole();
        $parentPath = '/rent/'.$parent->id;
        $this->get($parentPath)->assertOk()->assertSee('action="/rent/storeUpdate"', false);

        $payload = $this->rentFinancialPayload() + ['rent_id' => $parent->id];
        $this->post('/rent/storeUpdate', $payload + ['payment_evidence_file' => UploadedFile::fake()->image('extension.png')])
            ->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $extension = RentUpdate::sole();
        $originalPaymentFile = $extension->payment_evidence_file;
        Storage::disk('public')->assertExists('Rent_File/'.$originalPaymentFile);
        $this->assertTrue($extension->rent->is($parent));
        $this->assertSame(1, $parent->fresh()->rent_update->count());
        $this->assertDatabaseHas('rent_updates', [
            'id' => $extension->id, 'rent_id' => $parent->id, 'rent_code' => 'RENTUP0001',
            'rent_per_year' => 12000000, 'cvcs_fund' => 1500000, 'online_fund' => 500000,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31', 'user_id' => auth()->id(),
        ]);
        $this->get('/rent')->assertOk()->assertSee('CRUD BUILDING');
        $this->get($parentPath)->assertOk()->assertSee('RENTUP0001');
        $this->get('/rent/'.$extension->id.'/'.$parent->id.'/editUpdate')->assertOk()
            ->assertSee('action="/rent/'.$extension->id.'/updateRentUpdate"', false)
            ->assertSee('value="12000000"', false);

        $this->post('/rent/'.$extension->id.'/updateRentUpdate', array_replace($payload, [
            'rent_per_year' => 36000000, 'cvcs_fund' => 0,
            'join_date' => '01/01/2027', 'expired_date' => '31/12/2027', 'notes' => 'Revised rent extension',
            'payment_evidence_file' => UploadedFile::fake()->image('replacement.jpg'),
        ]))->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('rent_updates', 1);
        $this->assertDatabaseHas('rent_updates', [
            'id' => $extension->id, 'rent_id' => $parent->id, 'rent_code' => 'RENTUP0001',
            'rent_per_year' => 36000000, 'cvcs_fund' => 0, 'online_fund' => 500000,
            'join_date' => '2027-01-01', 'expired_date' => '2027-12-31', 'notes' => 'Revised rent extension',
        ]);
        $paymentFile = $extension->fresh()->payment_evidence_file;
        $this->assertNotSame($originalPaymentFile, $paymentFile);
        $this->assertStringEndsWith('.jpg', $paymentFile);
        Storage::disk('public')->assertExists('Rent_File/'.$paymentFile);
        $this->assertSame(12000000, $parent->fresh()->rent_per_year);
        $this->get($parentPath)->assertOk()->assertSee('Revised rent extension')->assertSee('36.000.000');

        $this->get('/rent/'.$extension->id.'/deleteUpdate/'.$parent->id)
            ->assertRedirect($parentPath)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted('rent_updates', ['id' => $extension->id]);
        $this->assertSame(0, $parent->fresh()->rent_update->count());
        $this->assertNotSoftDeleted('rents', ['id' => $parent->id]);
        $this->get($parentPath)->assertOk()->assertDontSee('Revised rent extension');
        $this->get('/rent/'.$extension->id.'/'.$parent->id.'/editUpdate')->assertNotFound();
    }

    private function insurancePayload(): array
    {
        return $this->insuranceFinancialPayload() + [
            'policy_number' => 'CRUD/POLICY-001', 'insured_name' => 'INSURED COMPANY',
            'insured_address' => 'Jakarta', 'warehouse_code' => 'WH-01', 'insured_detail' => 'CRUD WAREHOUSE',
            'risk_address' => 'Bandung', 'insurance_category_id' => 1, 'insurance_scope_id' => 1,
        ];
    }

    private function insuranceFinancialPayload(): array
    {
        return [
            'stock_inprov_id' => 1, 'building_inprov_id' => 1,
            'stock_worth' => 12000000, 'actual_stock_worth' => 10000000, 'stock_premium' => 125000,
            'building_worth' => 250000000, 'building_premium' => 250000,
            'join_date' => '01/01/2026', 'expired_date' => '31/12/2026',
            'status' => 'BERJALAN', 'notes' => 'Original insurance',
        ];
    }

    private function rentPayload(): array
    {
        return $this->rentFinancialPayload() + ['rented_address' => 'Jakarta', 'rented_detail' => 'CRUD BUILDING'];
    }

    private function rentFinancialPayload(): array
    {
        return [
            'first_party' => 'LANDLORD', 'second_party' => 'TENANT',
            'rent_per_year' => 12000000, 'cvcs_fund' => 1500000, 'online_fund' => 500000,
            'join_date' => '01/01/2026', 'expired_date' => '31/12/2026',
            'deduction_evidence' => 'ADA', 'document' => 'ADA', 'status' => 'BERJALAN',
            'month_before_reminder' => 2, 'notes' => 'Original rent',
        ];
    }
}
