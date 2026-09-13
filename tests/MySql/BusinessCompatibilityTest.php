<?php

namespace Tests\MySql;

use Illuminate\Support\Facades\DB;
use Tests\Concerns\ExercisesBusinessCompatibility;
use Tests\MySqlTestCase;

class BusinessCompatibilityTest extends MySqlTestCase
{
    use ExercisesBusinessCompatibility;

    public function test_midnight_cutoff_retains_mysql_date_comparison_behavior(): void
    {
        $this->travelTo(now()->setTime(0, 0));
        foreach ([1 => '2026-09-11 23:59:59', 2 => '2026-09-12 00:00:00', 3 => '2026-09-12 23:59:59'] as $id => $closedAt) {
            DB::table('problem_report')->insert([
                'id' => $id, 'date' => '2026-09-01 00:00:00', 'user_id' => $this->applicant->id,
                'pr_category_id' => 1, 'closed_at' => $closedAt, 'closed_by' => 1, 'status_client' => 0,
            ]);
        }

        // MySQL compares DATE(closed_at) as midnight against the timestamp.
        // At exactly midnight yesterday equals the cutoff; at 00:01 it is less.
        $this->artisan('autoapprove')->assertSuccessful();
        $this->assertSame([1 => 1, 2 => 0, 3 => 0],
            DB::table('problem_report')->orderBy('id')->pluck('status_client', 'id')->all());

        $this->travelTo(now()->setTime(0, 1));
        $this->artisan('autoapprove')->assertSuccessful();
        $this->assertSame([1 => 1, 2 => 1, 3 => 1],
            DB::table('problem_report')->orderBy('id')->pluck('status_client', 'id')->all());
    }
}
