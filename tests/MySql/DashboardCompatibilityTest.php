<?php

namespace Tests\MySql;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\MySqlTestCase;

class DashboardCompatibilityTest extends MySqlTestCase
{
    use RefreshDatabase;

    public function test_dashboard_queries_use_approved_quantities_for_warehouse_and_exclude_cancelled_requests(): void
    {
        DB::table('areas')->insert(['id' => 4, 'area' => 'WAREHOUSE']);
        DB::table('divisions')->insert(['id' => 1, 'division' => 'TEST DIVISION', 'area_id' => 4]);
        DB::table('badan_usahas')->insert(['id' => 1, 'badan_usaha' => 'TEST COMPANY']);
        DB::table('roles')->insert(['id' => 1, 'role' => 'ADMIN']);
        DB::table('request_type')->insert(['id' => 2, 'request_type' => 'TEST WAREHOUSE', 'pic_division_id' => 1]);
        $user = User::factory()->create(['fullname' => 'DASHBOARD USER']);
        DB::table('products')->insert(['id' => 1, 'product' => 'TEST ITEM', 'category_id' => 1, 'unit_type_id' => 1, 'price' => 2500]);
        foreach ([1 => 0, 2 => 2] as $id => $status) {
            DB::table('requests')->insert([
                'id' => $id, 'user_id' => $user->id, 'date' => '2026-09-13 10:00:00',
                'total_cost' => 999999, 'request_type_id' => 2, 'status_client' => $status,
            ]);
            DB::table('request_details')->insert([
                'request_id' => $id, 'product_id' => 1, 'qty_request' => 12, 'qty_approved' => 3,
            ]);
        }
        $this->actingAs($user)->get('/dashboard')->assertOk()
            ->assertViewHas('highestRequestUser', ['DASHBOARD USER'])
            ->assertViewHas('highestRequestUnit', fn ($values) => array_map('floatval', $values) === [3.0])
            ->assertViewHas('highestRequestCostUnit', fn ($values) => array_map('floatval', $values) === [7500.0]);
    }
}
