<?php

namespace Tests\MySql;

use App\Imports\InsuranceImport;
use App\Imports\InsuranceUpdateImport;
use App\Imports\ProductImport;
use App\Imports\RentImport;
use App\Imports\RentUpdateImport;
use App\Imports\UserImport;
use App\Models\Insurance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\MySqlTestCase;

class SpreadsheetCollationTest extends MySqlTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->actingAs(User::factory()->create(['username' => 'admin', 'fullname' => 'ADMIN USER']));
        $this->assertSame('mysql', DB::connection()->getDriverName());
    }

    public function test_product_reimport_matches_uppercase_insert_using_mysql_collation(): void
    {
        $row = ['nama_barang' => 'Mixed Case Product', 'kategori' => 'sta tionery', 'tipe_unit' => 'pcs', 'harga' => 1250, 'stok' => 5];
        $this->importRow(new ProductImport(), $row);
        $this->assertDatabaseHas('products', ['product' => 'MIXED CASE PRODUCT', 'category_id' => 1, 'unit_type_id' => 1]);

        $this->importRow(new ProductImport(), array_replace($row, ['nama_barang' => 'mixed case product', 'harga' => 2500, 'stok' => 0]));
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['price' => 2500, 'stock' => 0]);
    }

    public function test_rent_and_extension_reimport_match_generated_uppercase_codes(): void
    {
        $row = [
            'kode' => '', 'nama_bangunan' => 'BUILDING', 'pihak_pertama' => 'LANDLORD', 'pihak_kedua' => 'TENANT',
            'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-12-31', 'sewa_per_tahun' => 12000000,
        ];
        $this->importRow(new RentImport(), $row);
        $this->assertDatabaseHas('rents', ['rent_code' => 'RENT0001']);

        $this->importRow(new RentImport(), array_replace($row, ['kode' => 'rEnT0001', 'sewa_per_tahun' => 24000000]));
        $this->assertDatabaseCount('rents', 1);
        $this->assertDatabaseHas('rents', ['rent_code' => 'RENT0001', 'rent_per_year' => 24000000]);

        $extension = ['kode_sewa_induk' => 'rEnT0001'] + $row;
        $this->importRow(new RentUpdateImport(), $extension);
        $this->assertDatabaseHas('rent_updates', ['rent_code' => 'RENTUP0001']);
        $this->importRow(new RentUpdateImport(), array_replace($extension, ['kode' => 'rentup0001', 'sewa_per_tahun' => 36000000]));
        $this->assertDatabaseCount('rent_updates', 1);
        $this->assertDatabaseHas('rent_updates', ['rent_code' => 'RENTUP0001', 'rent_per_year' => 36000000]);
    }

    public function test_insurance_and_extension_reimport_keep_parent_scope_with_case_insensitive_lookups(): void
    {
        $row = [
            'no_polis' => 'POLICY-001', 'nama_tertanggung' => 'INSURED',
            'kategori_asuransi' => 'pro perty', 'cakupan_asuransi' => 'all risk', 'asuransi_stok' => 'pro vider',
            'tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-12-31', 'nilai_stok' => 12000000,
        ];
        $this->importRow(new InsuranceImport(), $row);
        $this->importRow(new InsuranceImport(), array_replace($row, ['no_polis' => 'policy-001', 'nilai_stok' => 24000000]));
        $this->assertDatabaseCount('insurances', 1);
        $this->assertDatabaseHas('insurances', ['stock_worth' => 24000000, 'stock_inprov_id' => 1, 'insurance_category_id' => 1, 'insurance_scope_id' => 1]);

        $parent = Insurance::firstOrFail();
        $extension = array_replace($row, ['no_polis_induk' => 'PoLiCy-001', 'no_polis' => 'UPDATE-001']);
        $this->importRow(new InsuranceUpdateImport($parent), $extension);
        $this->importRow(new InsuranceUpdateImport($parent), array_replace($extension, ['no_polis' => 'update-001', 'nilai_stok' => 36000000]));
        $this->assertDatabaseCount('insurance_updates', 1);
        $this->assertDatabaseHas('insurance_updates', ['insurance_id' => $parent->id, 'stock_worth' => 36000000]);
    }

    public function test_user_import_lookup_labels_and_approver_use_mysql_collation(): void
    {
        $row = [
            'username' => 'New.User', 'fullname' => 'New Person',
            'badan_usaha' => 'com pany', 'divisi' => 'general', 'role' => 'admin', 'approval' => 'admin user',
        ];
        $this->importRow(new UserImport(), $row);
        $this->importRow(new UserImport(), array_replace($row, ['username' => 'NEW.USER', 'fullname' => 'Updated Person']));

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'username' => 'new.user', 'fullname' => 'UPDATED PERSON',
            'badan_usaha_id' => 1, 'division_id' => 1, 'role_id' => 1, 'approval_id' => auth()->id(),
        ]);
    }

    private function importRow(Import $import, array $row): void
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([array_keys($row), array_values($row)], null, 'A1', true);
        $path = tempnam(sys_get_temp_dir(), 'gais-mysql-xlsx-');

        try {
            (new Xlsx($spreadsheet))->save($path);
            Excel::import($import, $path, null, \Maatwebsite\Excel\Excel::XLSX);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unlink($path);
        }
    }
}
