<?php

namespace Tests\Feature;

use App\Models\Insurance;
use App\Models\InsuranceUpdate;
use App\Models\Product;
use App\Models\Rent;
use App\Models\RentUpdate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SpreadsheetCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        foreach ([
            'areas' => ['area' => 'JAKARTA'],
            'badan_usahas' => ['badan_usaha' => 'COMPANY'],
            'divisions' => ['division' => 'GENERAL', 'area_id' => 1],
            'roles' => ['role' => 'ADMIN'],
            'categories' => ['category' => 'STATIONERY'],
            'unit_types' => ['unit_type' => 'PCS'],
            'insurance_providers' => ['insurance_provider' => 'PROVIDER'],
            'insurance_categories' => ['insurance_category' => 'PROPERTY'],
            'insurance_scopes' => ['insurance_scope' => 'ALLRISK'],
        ] as $table => $values) {
            DB::table($table)->insert(['id' => 1] + $values);
        }

        $this->actingAs(User::factory()->create([
            'username' => 'admin',
            'fullname' => 'ADMIN USER',
            'role_id' => 1,
        ]));
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_all_six_template_routes_return_xlsx_with_the_existing_heading_contract(): void
    {
        $templates = [
            '/user/export/template' => 'username,fullname,password,badan_usaha,divisi,role,approval',
            '/product/export/template' => 'nama_barang,kategori,tipe_unit,harga,keterangan,stok',
            '/insurance/export/template' => 'id,no_polis,alamat_tertanggung,nama_tertanggung,kode_gudang,detail_asuransi,alamat_yang_diasuransikan,asuransi_stok,nilai_stok,nilai_aktual_stok,premi_stok,asuransi_bangunan,nilai_bangunan,premi_bangunan,kategori_asuransi,tanggal_mulai,tanggal_akhir,cakupan_asuransi,status,catatan',
            '/insurance/export/templateUpdate' => 'no_polis_induk,no_polis,asuransi_stok,nilai_stok,nilai_aktual_stok,premi_stok,asuransi_bangunan,nilai_bangunan,premi_bangunan,tanggal_mulai,tanggal_akhir,catatan',
            '/rent/export/template' => 'kode,alamat_bangunan,nama_bangunan,pihak_pertama,pihak_kedua,sewa_per_tahun,dana_cvcs,dana_online,tanggal_mulai,tanggal_akhir,bukti_potong,berkas,status,reminder_bulan_sebelumnya,catatan',
            '/rent/export/templateUpdate' => 'kode_sewa_induk,kode,pihak_pertama,pihak_kedua,sewa_per_tahun,dana_cvcs,dana_online,tanggal_mulai,tanggal_akhir,bukti_potong,berkas,status,reminder_bulan_sebelumnya,catatan',
        ];

        foreach ($templates as $uri => $columns) {
            $rows = $this->downloadRows($uri);
            $this->assertSame([explode(',', $columns)], $rows, $uri);
        }
    }

    public function test_user_xlsx_import_inserts_updates_skips_and_exports_relations(): void
    {
        $existing = User::factory()->create(['username' => 'existing', 'fullname' => 'OLD NAME']);
        $password = $existing->password;
        $common = ['badan_usaha' => 'COMPANY', 'divisi' => 'GENERAL', 'role' => 'ADMIN', 'approval' => 'ADMIN USER'];

        $this->import('/user/import', [
            ['username' => ' New.User ', 'fullname' => 'New Person', 'password' => 'import-secret'] + $common,
            ['username' => 'EXISTING', 'fullname' => 'Updated Person', 'password' => 'must-not-replace'] + $common,
            ['username' => '', 'fullname' => 'SKIPPED'] + $common,
        ])->assertRedirect('/user')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['username' => 'new.user', 'fullname' => 'NEW PERSON', 'approval_id' => 1]);
        $this->assertTrue(Hash::check('import-secret', User::where('username', 'new.user')->value('password')));
        $this->assertSame($password, $existing->fresh()->password);
        $this->assertSame('UPDATED PERSON', $existing->fresh()->fullname);

        $rows = $this->downloadRows('/user/export');
        $this->assertSame(['username', 'fullname', 'badan_usaha', 'divisi', 'role', 'approval'], $rows[0]);
        $this->assertContains(['new.user', 'NEW PERSON', 'COMPANY', 'GENERAL', 'ADMIN', 'ADMIN USER'], $rows);
    }

    public function test_product_xlsx_import_inserts_updates_skips_and_exports_numeric_values(): void
    {
        // Existing lowercase data also permits this branch to run on SQLite;
        // case-insensitive re-imports of generated uppercase names need MySQL.
        $existing = Product::create(['product' => 'paper', 'category_id' => 1, 'unit_type_id' => 1, 'price' => 10, 'stock' => 8]);
        $common = ['kategori' => 'STATIONERY', 'tipe_unit' => 'PCS'];

        $this->import('/product/import', [
            ['nama_barang' => 'Envelope', 'harga' => 1500, 'keterangan' => 'New stock', 'stok' => 12] + $common,
            ['nama_barang' => 'Paper', 'harga' => 2000, 'keterangan' => 'Updated', 'stok' => 0] + $common,
            ['nama_barang' => '', 'harga' => 999] + $common,
        ])->assertRedirect('/product')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('products', 2);
        $this->assertDatabaseHas('products', ['id' => $existing->id, 'product' => 'PAPER', 'price' => 2000, 'stock' => 0]);
        $rows = $this->downloadRows('/product/export');
        $this->assertSame(['nama_barang', 'kategori', 'tipe_unit', 'harga', 'keterangan', 'stok'], $rows[0]);
        $this->assertEquals(['ENVELOPE', 'STATIONERY', 'PCS', 1500, 'New stock', 12], $rows[1]);
        $this->assertSame('PAPER', $rows[2][0]);
        $this->assertEquals(2000, $rows[2][3]);
    }

    public function test_insurance_xlsx_import_inserts_updates_by_id_skips_and_exports_dates(): void
    {
        $existing = $this->insurance('OLD/POLICY');
        $this->import('/insurance/import', [
            $this->insuranceRow(['id' => null, 'no_polis' => 'NEW/POLICY', 'nilai_aktual_stok' => 0]),
            $this->insuranceRow(['id' => $existing->id, 'no_polis' => 'RENAMED/POLICY', 'nama_tertanggung' => 'UPDATED INSURED']),
            $this->insuranceRow(['id' => null, 'no_polis' => '']),
        ])->assertRedirect('/insurance')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('insurances', 2);
        $this->assertDatabaseHas('insurances', ['id' => $existing->id, 'policy_number' => 'RENAMED/POLICY', 'insured_name' => 'UPDATED INSURED']);
        $this->assertDatabaseHas('insurances', ['policy_number' => 'NEW/POLICY', 'actual_stock_worth' => 0]);
        $rows = $this->downloadRows('/insurance/export');
        $this->assertCount(3, $rows);
        $this->assertContains('id', $rows[0]);
        $newRow = collect(array_slice($rows, 1))->first(fn ($row) => $row[1] === 'NEW/POLICY');
        $this->assertSame('PROVIDER', $newRow[7]);
        $this->assertSame('PROPERTY', $newRow[14]);
        $this->assertSame(['2026-01-01', '2026-12-31', 'ALLRISK'], array_slice($newRow, 15, 3));
    }

    public function test_insurance_update_xlsx_import_self_saves_once_and_scopes_updates_to_parent(): void
    {
        $parent = $this->insurance('PARENT/POLICY');
        $otherParent = $this->insurance('OTHER/POLICY');
        $existing = InsuranceUpdate::create($this->insuranceUpdateValues($parent->id, 'UPDATE-OLD'));
        $other = InsuranceUpdate::create($this->insuranceUpdateValues($otherParent->id, 'UPDATE-OLD'));

        $this->import('/insurance/importUpdate', [
            $this->insuranceUpdateRow(['no_polis' => 'UPDATE-NEW', 'nilai_aktual_stok' => 0]),
            $this->insuranceUpdateRow(['no_polis' => 'UPDATE-OLD', 'catatan' => 'Updated notes']),
            $this->insuranceUpdateRow(['no_polis_induk' => '', 'no_polis' => '']),
        ], ['insurance_id' => $parent->id])->assertRedirect('/insurance/'.$parent->id)->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('insurance_updates', 3);
        $this->assertSame('Updated notes', $existing->fresh()->notes);
        $this->assertSame('Original notes', $other->fresh()->notes);
        $this->assertDatabaseHas('insurance_updates', ['insurance_id' => $parent->id, 'policy_number' => 'UPDATE-NEW', 'actual_stock_worth' => 0]);

        $rows = $this->downloadRows('/insurance/'.$parent->id.'/exportUpdate');
        $this->assertCount(3, $rows);
        $this->assertSame('no_polis_induk', $rows[0][0]);
        $this->assertSame('PARENT/POLICY', $rows[1][0]);
        $this->assertSame('PROVIDER', $rows[1][2]);
        $this->assertSame(['2026-01-01', '2026-12-31'], array_slice($rows[1], 9, 2));
    }

    public function test_rent_xlsx_import_inserts_updates_skips_and_exports_amounts_and_dates(): void
    {
        $existing = $this->rent('rent0099');
        $this->import('/rent/import', [
            $this->rentRow(['kode' => '', 'nama_bangunan' => 'NEW BUILDING']),
            $this->rentRow(['kode' => 'RENT0099', 'nama_bangunan' => 'UPDATED BUILDING', 'sewa_per_tahun' => 24000000, 'dana_online' => 0]),
            $this->rentRow(['kode' => '', 'nama_bangunan' => '', 'alamat_bangunan' => '']),
        ])->assertRedirect('/rent')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('rents', 2);
        $this->assertDatabaseHas('rents', ['rent_code' => 'RENT0002', 'rented_detail' => 'NEW BUILDING']);
        $this->assertDatabaseHas('rents', ['id' => $existing->id, 'rented_detail' => 'UPDATED BUILDING', 'rent_per_year' => 24000000, 'online_fund' => 0]);
        $rows = $this->downloadRows('/rent/export');
        $row = collect(array_slice($rows, 1))->first(fn ($row) => $row[0] === 'rent0099');
        $this->assertSame(24000000, $row[5]);
        $this->assertSame(['2026-01-01', '2026-12-31'], array_slice($row, 8, 2));
    }

    public function test_rent_update_xlsx_import_self_saves_once_updates_and_skips_missing_parents(): void
    {
        $parent = $this->rent('rent0099');
        $existing = RentUpdate::create([
            'rent_id' => $parent->id, 'rent_code' => 'RENTUP0099',
            'first_party' => 'LANDLORD', 'second_party' => 'TENANT',
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31', 'user_id' => 1,
        ]);
        $common = ['kode_sewa_induk' => 'RENT0099'];

        $this->import('/rent/importUpdate', [
            $common + $this->rentRow(['kode' => '', 'catatan' => 'New extension']),
            $common + $this->rentRow(['kode' => 'RENTUP0099', 'catatan' => 'Updated extension', 'sewa_per_tahun' => 24000000]),
            ['kode_sewa_induk' => 'MISSING'] + $this->rentRow(),
            ['kode_sewa_induk' => ''] + $this->rentRow(),
        ], ['rent_id' => $parent->id])->assertRedirect('/rent/'.$parent->id)->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseCount('rent_updates', 2);
        $this->assertDatabaseHas('rent_updates', ['rent_code' => 'RENTUP0002', 'rent_id' => $parent->id]);
        $this->assertSame('Updated extension', $existing->fresh()->notes);
        $rows = $this->downloadRows('/rent/'.$parent->id.'/exportUpdate');
        $this->assertCount(3, $rows);
        $this->assertSame('kode_sewa_induk', $rows[0][0]);
        $this->assertSame('rent0099', $rows[1][0]);
        $this->assertSame(24000000, $rows[1][4]);
    }

    public function test_invalid_xlsx_rows_roll_back_preceding_inserts_and_preserve_existing_data(): void
    {
        $this->import('/product/import', [
            ['nama_barang' => 'Valid first row', 'kategori' => 'STATIONERY', 'tipe_unit' => 'PCS'],
            ['nama_barang' => 'Invalid second row', 'kategori' => 'MISSING', 'tipe_unit' => 'PCS'],
        ])->assertSessionHas('error', fn ($message) => str_contains($message, 'tidak ditemukan'));
        $this->assertDatabaseCount('products', 0);

        $parent = $this->insurance('PARENT/POLICY');
        $this->import('/insurance/importUpdate', [
            $this->insuranceUpdateRow(['no_polis' => 'VALID-FIRST']),
            $this->insuranceUpdateRow(['no_polis_induk' => 'WRONG-PARENT', 'no_polis' => 'INVALID-SECOND']),
        ], ['insurance_id' => $parent->id])->assertSessionHas('error', fn ($message) => str_contains($message, 'tidak sesuai'));
        $this->assertDatabaseCount('insurance_updates', 0);
        $this->assertSame('PARENT/POLICY', $parent->fresh()->policy_number);
    }

    public function test_lookup_date_parent_and_file_validation_are_preserved(): void
    {
        $parent = $this->insurance('PARENT/POLICY');
        $rent = $this->rent('rent0099');

        foreach ([
            ['/user/import', [['username' => 'new', 'badan_usaha' => 'UNKNOWN']], [], 'tidak ditemukan'],
            ['/insurance/import', [$this->insuranceRow(['tanggal_mulai' => '2026-02-30'])], [], 'tidak valid'],
            ['/insurance/importUpdate', [$this->insuranceUpdateRow(['tanggal_mulai' => ''])], ['insurance_id' => $parent->id], 'wajib diisi'],
            ['/rent/import', [$this->rentRow(['tanggal_mulai' => '2026-02-30'])], [], 'tidak valid'],
            ['/rent/importUpdate', [['kode_sewa_induk' => 'RENT0099'] + $this->rentRow(['tanggal_mulai' => ''])], ['rent_id' => $rent->id], 'wajib diisi'],
        ] as [$uri, $rows, $parameters, $error]) {
            $this->import($uri, $rows, $parameters)
                ->assertSessionHas('error', fn ($message) => str_contains($message, $error));
        }

        $this->post('/product/import', ['fileImport' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain')])
            ->assertRedirect('/product')->assertSessionHas('error');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('rents', 1);
        $this->assertDatabaseCount('insurances', 1);
        $this->assertDatabaseCount('rent_updates', 0);
        $this->assertDatabaseCount('insurance_updates', 0);
    }

    public function test_request_exports_preserve_multirow_mapping_date_filters_and_null_versus_zero_totals(): void
    {
        DB::table('request_type')->insert(['id' => 1, 'request_type' => 'STATIONERY', 'pic_division_id' => 1]);
        $product = Product::create(['product' => 'PAPER', 'category_id' => 1, 'unit_type_id' => 1, 'price' => 2000]);
        foreach ([1 => ['2026-01-15 12:00:00', 0], 2 => ['2025-12-01 12:00:00', 0], 3 => ['2026-01-15 12:00:00', 2]] as $id => [$date, $status]) {
            DB::table('requests')->insert([
                'id' => $id, 'user_id' => 1, 'request_type_id' => 1, 'total_cost' => 0,
                'date' => $date, 'created_at' => $date, 'status_client' => $status,
            ]);
        }
        foreach ([[1, 1, 5, null], [2, 1, 7, 0], [3, 2, 99, null], [4, 3, 88, null]] as [$id, $requestId, $requested, $approved]) {
            DB::table('request_details')->insert([
                'id' => $id, 'request_id' => $requestId, 'product_id' => $product->id,
                'qty_request' => $requested, 'qty_approved' => $approved,
            ]);
        }

        $range = '2026-01-01 - 2026-01-31';
        $totals = $this->downloadRows('/request/export', [
            'exportRequest' => $range, 'area_id' => 1, 'request_type_id' => 1, 'selectFilterRequest' => 3,
        ]);
        $this->assertSame(['Barang', 'Tipe Unit', 'Harga', 'GENERAL', 'Total Item', 'Total Biaya'], $totals[0]);
        $this->assertEquals(['PAPER', 'PCS', 2000, 5, 5, 10000], $totals[1]);
        $this->assertEquals(['Total Item per Divisi', null, null, 5, 5, null], $totals[2]);
        $this->assertEquals(['Total Biaya per Divisi', null, null, 10000, null, 10000], $totals[3]);

        $qrRows = $this->downloadRows('/request/exportMasterQR', ['exportQR' => $range, 'request_type_id' => 1]);
        $this->assertEquals([
            ['id', 'nama_barang', 'pemohon'],
            [1, 'PAPER', 'ADMIN USER'],
            [2, 'PAPER', 'ADMIN USER'],
            [4, 'PAPER', 'ADMIN USER'],
        ], $qrRows);
    }

    public function test_problem_report_export_preserves_mapping_and_date_filter(): void
    {
        DB::table('problem_report_categories')->insert(['id' => 1, 'problem_report_category' => 'ELECTRICAL']);
        foreach ([1 => '2026-01-15 12:00:00', 2 => '2025-12-01 12:00:00'] as $id => $date) {
            DB::table('problem_report')->insert([
                'id' => $id, 'user_id' => 1, 'date' => $date, 'pr_category_id' => 1,
                'description' => 'Broken light', 'status' => 'CLOSED', 'status_client' => 1,
                'closed_by' => 1, 'closed_at' => '2026-01-20 12:00:00', 'result_desc' => 'Replaced',
            ]);
        }

        $rows = $this->downloadRows('/problemReport/export', ['exportProblemReport' => '2026-01-01 - 2026-01-31']);
        $this->assertCount(2, $rows);
        $this->assertSame(['ADMIN USER', '2026-01-15 12:00:00', 'ELECTRICAL', 'Broken light', 'CLOSED', null, 'ADMIN USER', '2026-01-20 12:00:00', 'Replaced', 'SELESAI'], $rows[1]);
    }

    private function import(string $uri, array $rows, array $parameters = []): TestResponse
    {
        $columns = array_values(array_unique(array_merge(...array_map('array_keys', $rows))));
        $values = [$columns];
        foreach ($rows as $row) {
            $values[] = array_map(fn ($column) => $row[$column] ?? null, $columns);
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($values, null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'gais-xlsx-');
        $this->temporaryFiles[] = $path;
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $this->post($uri, $parameters + [
            'fileImport' => new UploadedFile($path, 'fixture.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ]);
    }

    private function downloadRows(string $uri, ?array $parameters = null): array
    {
        $response = ($parameters === null ? $this->get($uri) : $this->post($uri, $parameters))->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->temporaryFiles[] = $path;
        $this->assertSame('Xlsx', IOFactory::identify($path));
        $spreadsheet = IOFactory::load($path);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, false, false);
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    private function insurance(string $policy): Insurance
    {
        return Insurance::create([
            'policy_number' => $policy, 'insured_name' => 'INSURED',
            'insurance_category_id' => 1, 'insurance_scope_id' => 1,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31', 'user_id' => 1,
        ]);
    }

    private function insuranceRow(array $overrides = []): array
    {
        return array_replace([
            'no_polis' => 'POLICY', 'nama_tertanggung' => 'INSURED',
            'kategori_asuransi' => 'PROPERTY', 'cakupan_asuransi' => 'ALLRISK',
            'asuransi_stok' => 'PROVIDER', 'nilai_stok' => 12000000,
            'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-12-31',
        ], $overrides);
    }

    private function insuranceUpdateValues(int $parent, string $policy): array
    {
        return [
            'insurance_id' => $parent, 'policy_number' => $policy,
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31',
            'user_id' => 1, 'notes' => 'Original notes',
        ];
    }

    private function insuranceUpdateRow(array $overrides = []): array
    {
        return array_replace([
            'no_polis_induk' => 'PARENT/POLICY', 'no_polis' => 'UPDATE',
            'asuransi_stok' => 'PROVIDER', 'nilai_stok' => 12000000,
            'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-12-31',
        ], $overrides);
    }

    private function rent(string $code): Rent
    {
        return Rent::create([
            'rent_code' => $code, 'rented_detail' => 'BUILDING',
            'first_party' => 'LANDLORD', 'second_party' => 'TENANT',
            'join_date' => '2026-01-01', 'expired_date' => '2026-12-31', 'user_id' => 1,
        ]);
    }

    private function rentRow(array $overrides = []): array
    {
        return array_replace([
            'kode' => '', 'nama_bangunan' => 'BUILDING', 'alamat_bangunan' => 'JAKARTA',
            'pihak_pertama' => 'LANDLORD', 'pihak_kedua' => 'TENANT',
            'sewa_per_tahun' => 12000000, 'dana_cvcs' => 1500000, 'dana_online' => 500000,
            'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-12-31',
        ], $overrides);
    }
}
