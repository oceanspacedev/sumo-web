<?php

namespace Tests\Concerns;

use App\Models\Area;
use App\Models\BadanUsaha;
use App\Models\Category;
use App\Models\Divisi;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProvider;
use App\Models\InsuranceScope;
use App\Models\PRCategory;
use App\Models\RequestSetting;
use App\Models\RequestType;
use App\Models\Role;
use App\Models\UnitType;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;

trait ExercisesMasterDataCrud
{
    use RefreshDatabase;

    private Area $initialArea;
    private Area $updatedArea;
    private Divisi $initialDivision;
    private Divisi $updatedDivision;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initialArea = Area::create(['area' => 'ADMIN AREA']);
        $this->updatedArea = Area::create(['area' => 'SECOND AREA']);
        $this->initialDivision = Divisi::create(['division' => 'ADMIN DIVISION', 'area_id' => $this->initialArea->id]);
        $this->updatedDivision = Divisi::create(['division' => 'SECOND DIVISION', 'area_id' => $this->updatedArea->id]);
        $business = BadanUsaha::create(['badan_usaha' => 'ADMIN BUSINESS']);
        Role::forceCreate(['id' => 1, 'role' => 'ADMIN']);
        $this->actingAs(User::factory()->create([
            'role_id' => 1, 'division_id' => $this->initialDivision->id, 'badan_usaha_id' => $business->id,
        ]));
    }

    public static function masterResources(): array
    {
        // Field names and verbs match each rendered create/edit form.
        return [
            'area' => [Area::class, 'area', 'create', 'area', 'settings.area', 'areas', 'area'],
            'business entity' => [BadanUsaha::class, 'bu', 'create', 'badan_usaha', 'settings.bu', 'badan_usahas', 'badan_usaha'],
            'product category' => [Category::class, 'category', 'create', 'category', 'master.category', 'categories', 'category'],
            'division' => [Divisi::class, 'division', 'create', 'division', 'settings.division', 'divisions', 'division'],
            'role' => [Role::class, 'role', 'create', 'role', 'settings.role', 'roles', 'role'],
            'unit type' => [UnitType::class, 'unittype', 'create', 'unit_type', 'master.unit_type', 'unit_types', 'unit_type'],
            'request type' => [RequestType::class, 'requesttype', 'create', 'request_type', 'master.request_type', 'request_types', 'request_type'],
            'problem report category' => [PRCategory::class, 'prcategory', 'create', 'problem_report_category', 'settings.prcategory', 'prcategories', 'prcategory'],
            'insurance category' => [InsuranceCategory::class, 'incategory', 'store', 'insurance_category', 'insurances.incategory', 'incategories', 'incategory'],
            'insurance provider' => [InsuranceProvider::class, 'inprov', 'store', 'insurance_provider', 'insurances.inprov', 'inprovs', 'inprov'],
            'insurance scope' => [InsuranceScope::class, 'inscope', 'store', 'insurance_scope', 'insurances.inscope', 'inscopes', 'inscope'],
        ];
    }

    #[DataProvider('masterResources')]
    public function test_admin_can_list_create_edit_update_and_delete_master_data(
        string $model, string $slug, string $createAction, string $field,
        string $view, string $listKey, string $editKey
    ): void {
        $initialCount = $model::count();
        $name = 'CRUD '.strtoupper($slug);
        $payload = [$field => $name];
        if ($slug === 'division') {
            $payload['area_id'] = $this->initialArea->id;
        } elseif ($slug === 'requesttype') {
            $payload['pic_division_id'] = $this->initialDivision->id;
        }

        $this->get('/'.$slug)->assertOk()->assertViewIs($view.'.index');
        $this->post('/'.$slug.'/'.$createAction, $payload)
            ->assertRedirect('/'.$slug)->assertSessionHas('success')->assertSessionMissing('error');

        $record = $model::where($field, $name)->sole();
        $this->assertSame($initialCount + 1, $model::count());
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id] + $payload);
        $this->get('/'.$slug)->assertOk()->assertSee($name)
            ->assertViewHas($listKey, fn ($rows) => $rows->contains('id', $record->id));
        $this->get('/'.$slug.'/'.$record->id.'/edit')->assertOk()->assertViewIs($view.'.edit')
            ->assertViewHas($editKey, fn ($bound) => $bound->id === $record->id)
            ->assertSee($name);

        $payload[$field] = $name.' UPDATED';
        if ($slug === 'division') {
            $payload['area_id'] = $this->updatedArea->id;
        } elseif ($slug === 'requesttype') {
            $payload['pic_division_id'] = $this->updatedDivision->id;
        }
        $this->flushSession();
        $this->post('/'.$slug.'/'.$record->id.'/update', $payload)
            ->assertRedirect('/'.$slug)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id] + $payload);
        $this->assertSame($initialCount + 1, $model::count());
        $this->get('/'.$slug)->assertOk()->assertSee($payload[$field]);

        $this->flushSession();
        $this->get('/'.$slug.'/'.$record->id.'/delete')
            ->assertRedirect('/'.$slug)->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSoftDeleted($record);
        $this->assertSame($initialCount, $model::count());
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id] + $payload);

        if ($slug === 'division') {
            // Division's list intentionally retains inactive records and exposes restoration.
            $this->get('/division')->assertOk()->assertSee($payload[$field])
                ->assertSee('/division/'.$record->id.'/active', false);
            $this->flushSession();
            $this->get('/division/'.$record->id.'/active')->assertRedirect('/division')
                ->assertSessionHas('success')->assertSessionMissing('error');
            $this->assertNotSoftDeleted($record);
            $this->assertDatabaseHas('divisions', ['id' => $record->id, 'area_id' => $this->updatedArea->id, 'division' => $payload[$field]]);
            $this->get('/division')->assertOk()->assertSee($payload[$field])
                ->assertSee('/division/'.$record->id.'/delete', false);
        } else {
            $this->get('/'.$slug)->assertOk()
                ->assertViewHas($listKey, fn ($rows) => ! $rows->contains('id', $record->id));
        }
    }

    public function test_request_settings_can_be_listed_created_edited_and_updated(): void
    {
        $this->get('/request-settings')->assertOk()->assertViewIs('settings.request-settings.index');
        $this->post('/request-settings/create', [
            'request_detail' => 'September stationery period', 'request_month' => '09-2026',
            'open_date' => '10/09/2026', 'closed_date' => '20/09/2026',
        ])->assertRedirect('/request-settings')->assertSessionHas('success')->assertSessionMissing('error');
        $setting = RequestSetting::sole();
        $this->assertDatabaseHas('request_settings', [
            'id' => $setting->id, 'request_detail' => 'September stationery period',
            'request_month' => '2026-09-01', 'open_date' => '2026-09-10', 'closed_date' => '2026-09-20',
        ]);
        $this->get('/request-settings')->assertOk()->assertSee('September stationery period')
            ->assertViewHas('requestSettings', fn ($rows) => $rows->contains('id', $setting->id));
        $this->get('/request-settings/'.$setting->id.'/edit')->assertOk()
            ->assertViewIs('settings.request-settings.edit')
            ->assertViewHas('requestSetting', fn ($bound) => $bound->id === $setting->id)
            ->assertSee('September stationery period');

        $this->flushSession();
        $this->post('/request-settings/'.$setting->id.'/update', [
            'request_detail' => 'October stationery period', 'request_month' => '10-2026',
            'open_date' => '05/10/2026', 'closed_date' => '15/10/2026',
        ])->assertRedirect('/request-settings')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseCount('request_settings', 1);
        $this->assertDatabaseHas('request_settings', [
            'id' => $setting->id, 'request_detail' => 'October stationery period',
            'request_month' => '2026-10-01', 'open_date' => '2026-10-05', 'closed_date' => '2026-10-15',
        ]);
        $this->get('/request-settings')->assertOk()->assertSee('October stationery period');
    }

    public function test_request_settings_edit_prefills_dates_and_saves_unchanged_rendered_values(): void
    {
        $setting = RequestSetting::create([
            'request_detail' => 'September stationery period', 'request_month' => '2026-09-01',
            'open_date' => '2026-09-10', 'closed_date' => '2026-09-20',
        ]);
        $fields = ['request_detail', 'request_month', 'open_date', 'closed_date'];
        $original = $setting->fresh()->only($fields);
        $updatePath = '/request-settings/'.$setting->id.'/update';
        $html = $this->get('/request-settings/'.$setting->id.'/edit')->assertOk()->getContent();

        foreach (['request_month', 'open_date', 'closed_date'] as $field) {
            preg_match_all('/<input\b[^>]*\bname="'.$field.'"[^>]*>/i', $html, $inputs);
            $this->assertCount(1, $inputs[0], 'Expected one input for '.$field);
            $this->assertSame(1, preg_match_all('/\svalue\s*=/i', $inputs[0][0]),
                $field.' must have exactly one value attribute');
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $payload = [];
        foreach ($xpath->query('//form[@action="'.$updatePath.'"]//input[@name]') as $input) {
            $payload[$input->getAttribute('name')] = $input->getAttribute('value');
        }
        $this->assertSame('09-2026', $payload['request_month']);
        $this->assertSame('10/09/2026', $payload['open_date']);
        $this->assertSame('20/09/2026', $payload['closed_date']);
        $this->assertSame('September stationery period', $payload['request_detail']);

        $this->post($updatePath, $payload)->assertRedirect('/request-settings')
            ->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSame($original, $setting->fresh()->only($fields));
        $this->assertDatabaseCount('request_settings', 1);
    }
}
