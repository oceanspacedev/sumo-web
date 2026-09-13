<?php

namespace Tests\Concerns;

use App\Models\ProblemReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

trait ExercisesProblemReportCrud
{
    use RefreshDatabase;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 9, 13)->setTime(10, 0));
        Storage::fake('public');
        DB::table('areas')->insert(['id' => 1, 'area' => 'CRUD TEST AREA']);
        foreach ([1, 6, 11] as $id) {
            DB::table('divisions')->insert([
                'id' => $id, 'division' => 'CRUD TEST DIVISION '.$id, 'area_id' => 1,
            ]);
        }
        DB::table('problem_report_categories')->insert([
            ['id' => 1, 'problem_report_category' => 'TECHNICAL FIXTURE'],
            ['id' => 7, 'problem_report_category' => 'GENERAL FIXTURE'],
        ]);
        $this->reporter = User::factory()->create([
            'fullname' => 'CRUD TEST REPORTER', 'role_id' => 4, 'division_id' => 1,
        ]);
        $this->actingAs($this->reporter);
    }

    public function test_report_can_be_created_scheduled_closed_and_acknowledged_with_photo_replacement(): void
    {
        $this->get('/problemReport/create')->assertOk()->assertViewIs('problems.addProblem');
        $before = UploadedFile::fake()->image('before.jpg', 24, 16);
        $beforeBytes = file_get_contents($before->getRealPath());

        $this->post('/problemReport/store', [
            'user_id' => $this->reporter->id,
            'pr_category_id' => 1,
            'description' => 'CRUD fixture broken lamp',
            'photo_before' => $before,
        ])->assertRedirect('/problemReport')
            ->assertSessionHas('success', 'Laporan berhasil diinput !')->assertSessionMissing('error');

        $problem = ProblemReport::sole();
        $this->assertSame('PR'.$problem->id, $problem->problem_report_code);
        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'user_id' => $this->reporter->id,
            'description' => 'CRUD fixture broken lamp', 'pr_category_id' => 1,
            'date' => '2026-09-13 10:00:00', 'status' => 'PENDING',
            'status_client' => 0, 'closed_at' => null, 'closed_by' => null,
        ]);
        $this->assertStringStartsWith('PR-BEFORE-2026-09-13_', $problem->photo_before);
        $beforePath = 'Problem_Report_File/'.$problem->photo_before;
        Storage::disk('public')->assertExists($beforePath);
        $this->assertSame($beforeBytes, Storage::disk('public')->get($beforePath));
        $this->get('/problemReport')->assertOk()->assertSee('CRUD fixture broken lamp');

        $this->get('/problemReport/create')->assertRedirect('/problemReport')
            ->assertSessionHas('error', 'Harap menunggu hingga diproses dan status akhir diselesaikan !');
        $this->flushSession();

        $executor = User::factory()->create(['role_id' => 3, 'division_id' => 11]);
        $this->actingAs($executor)->get('/problemReport/'.$problem->id.'/editStatus')
            ->assertOk()->assertViewIs('problems.editStatus')->assertSee($this->reporter->fullname);
        $pendingPhoto = UploadedFile::fake()->image('work-in-progress.png', 20, 12);
        $pendingBytes = file_get_contents($pendingPhoto->getRealPath());
        $this->post('/problemReport/'.$problem->id.'/updateStatus', [
            'scheduled_at' => '09/14/2026', 'status' => 'PENDING',
            'result_desc' => 'Work scheduled', 'photo_after' => $pendingPhoto,
        ])->assertRedirect('/problemReport')->assertSessionHas('success')->assertSessionMissing('error');
        $problem->refresh();
        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'scheduled_at' => '2026-09-14',
            'status' => 'PENDING', 'result_desc' => 'Work scheduled',
            'closed_by' => null, 'closed_at' => null, 'status_client' => 0,
        ]);
        $firstAfterName = $problem->photo_after;
        $this->assertStringEndsWith('.png', $firstAfterName);
        $this->assertSame($pendingBytes, Storage::disk('public')->get('Problem_Report_File/'.$firstAfterName));

        $this->travelTo(now()->setDate(2026, 9, 14)->setTime(15, 30));
        $completedPhoto = UploadedFile::fake()->image('completed.jpg', 32, 16);
        $completedBytes = file_get_contents($completedPhoto->getRealPath());
        $this->post('/problemReport/'.$problem->id.'/updateStatus', [
            'status' => 'CLOSED', 'result_desc' => 'Lamp replaced', 'photo_after' => $completedPhoto,
        ])->assertRedirect('/problemReport')->assertSessionHas('success')->assertSessionMissing('error');
        $problem->refresh();
        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'status' => 'CLOSED', 'result_desc' => 'Lamp replaced',
            'closed_by' => $executor->id, 'closed_at' => '2026-09-14 15:30:00',
            'scheduled_at' => '2026-09-14', 'status_client' => 0,
        ]);
        $this->assertNotSame($firstAfterName, $problem->photo_after);
        $this->assertStringStartsWith('PR-AFTER-2026-09-14_', $problem->photo_after);
        $this->assertSame($completedBytes, Storage::disk('public')->get('Problem_Report_File/'.$problem->photo_after));
        $this->assertSame($beforeBytes, Storage::disk('public')->get($beforePath));

        $this->actingAs($this->reporter)->get('/problemReport/'.$problem->id.'/editStatusClient')
            ->assertOk()->assertViewIs('problems.editStatusClient')->assertSee('CRUD fixture broken lamp');
        $this->post('/problemReport/'.$problem->id.'/updateStatusClient', ['status_client' => 1])
            ->assertRedirect('/problemReport')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'status' => 'CLOSED', 'status_client' => 1,
            'closed_by' => $executor->id, 'closed_at' => '2026-09-14 15:30:00',
        ]);
        $this->get('/problemReport/create')->assertOk();
    }

    public function test_cancellation_sets_the_executor_time_and_client_completion(): void
    {
        $problem = $this->reportFixture($this->reporter);
        $admin = User::factory()->create(['role_id' => 1]);
        $this->actingAs($admin)->post('/problemReport/'.$problem->id.'/updateStatus', [
            'status' => 'CANCELLED', 'result_desc' => 'Duplicate report',
        ])->assertRedirect('/problemReport')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'status' => 'CANCELLED', 'status_client' => 1,
            'result_desc' => 'Duplicate report', 'closed_by' => $admin->id,
            'closed_at' => '2026-09-13 10:00:00',
        ]);
        $this->actingAs($this->reporter)->get('/problemReport/create')->assertOk();
    }

    public function test_required_category_and_photo_are_rejected_without_inserting_a_report(): void
    {
        $payload = ['user_id' => $this->reporter->id, 'description' => 'Invalid fixture'];
        $this->post('/problemReport/store', $payload)->assertRedirect('/problemReport/create')
            ->assertSessionHas('error', 'Harap pilih jenis gangguan !');
        $this->assertDatabaseCount('problem_report', 0);
        $this->flushSession();

        $this->post('/problemReport/store', $payload + ['pr_category_id' => 1])
            ->assertRedirect('/problemReport/create')
            ->assertSessionHas('error', 'Harap upload foto gangguan/kerusakan !');
        $this->assertDatabaseCount('problem_report', 0);
        $this->flushSession();

        $this->post('/problemReport/store', $payload + [
            'pr_category_id' => 1,
            'photo_before' => UploadedFile::fake()->create('invalid.txt', 1, 'text/plain'),
        ])->assertRedirect('/problemReport')->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertDatabaseCount('problem_report', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_default_lists_preserve_admin_manager_category_and_reporter_scopes(): void
    {
        $technical = $this->reportFixture($this->reporter);
        $general = $this->reportFixture($this->reporter, ['pr_category_id' => 7]);
        $otherUser = User::factory()->create(['role_id' => 4]);
        $otherTechnical = $this->reportFixture($otherUser);
        $otherGeneral = $this->reportFixture($otherUser, ['pr_category_id' => 7]);
        $this->reportFixture($this->reporter)->delete();

        foreach ([
            [User::factory()->create(['role_id' => 1]), [$technical->id, $general->id, $otherTechnical->id, $otherGeneral->id]],
            [User::factory()->create(['role_id' => 3, 'division_id' => 6]), [$general->id, $otherGeneral->id]],
            [User::factory()->create(['role_id' => 3, 'division_id' => 11]), [$technical->id, $otherTechnical->id]],
            [$this->reporter, [$technical->id, $general->id]],
        ] as [$user, $expectedIds]) {
            $response = $this->actingAs($user)->get('/problemReport')->assertOk();
            $this->assertEqualsCanonicalizing($expectedIds, $response->viewData('problems')->getCollection()->modelKeys());
        }
    }

    public function test_reporter_name_and_date_searches_stay_within_the_reporters_records(): void
    {
        $matching = $this->reportFixture($this->reporter);
        $older = $this->reportFixture($this->reporter, ['date' => '2026-08-01 10:00:00']);
        $other = User::factory()->create(['role_id' => 4, 'fullname' => $this->reporter->fullname]);
        $this->reportFixture($other);

        $nameResponse = $this->get('/problemReport?'.http_build_query(['code' => 'CRUD TEST']))->assertOk();
        $this->assertEqualsCanonicalizing([$matching->id, $older->id],
            $nameResponse->viewData('problems')->getCollection()->modelKeys());
        $dateResponse = $this->get('/problemReport?'.http_build_query([
            'search' => '09/13/2026 - 09/13/2026',
        ]))->assertOk();
        $this->assertSame([$matching->id], $dateResponse->viewData('problems')->getCollection()->modelKeys());
    }

    public function test_admin_status_filter_returns_the_selected_client_status(): void
    {
        $this->reportFixture($this->reporter);
        $completed = $this->reportFixture($this->reporter, ['status' => 'CLOSED', 'status_client' => 1]);
        $admin = User::factory()->create(['role_id' => 1]);
        $response = $this->actingAs($admin)->get('/problemReport?selectStatusAkhir=1')->assertOk();

        $this->assertSame([$completed->id], $response->viewData('problems')->getCollection()->modelKeys());
    }

    public static function clientStatusFilters(): array
    {
        return ['pending' => [0], 'completed' => [1]];
    }

    #[DataProvider('clientStatusFilters')]
    public function test_reporter_status_filter_excludes_other_reporters_and_other_statuses(int $status): void
    {
        $own = $this->reportFixture($this->reporter, ['status_client' => $status]);
        $this->reportFixture($this->reporter, ['status_client' => 1 - $status]);
        $this->reportFixture($this->reporter, ['status_client' => $status])->delete();
        $other = User::factory()->create(['role_id' => 4]);
        $this->reportFixture($other, ['status_client' => $status]);
        $this->reportFixture($other, ['status_client' => 1 - $status]);

        $response = $this->get('/problemReport?selectStatusAkhir='.$status)->assertOk();

        $this->assertSame([$own->id], $response->viewData('problems')->getCollection()->modelKeys());
    }

    public static function statusEditsWithoutUploads(): array
    {
        return [
            'pending without file field' => ['PENDING', []],
            'closed with empty file field' => ['CLOSED', ['photo_after' => '']],
            'cancelled with null file field' => ['CANCELLED', ['photo_after' => null]],
        ];
    }

    #[DataProvider('statusEditsWithoutUploads')]
    public function test_status_edit_without_new_upload_preserves_existing_photo_references_and_bytes(
        string $status,
        array $filePayload,
    ): void {
        $problem = $this->reportFixture($this->reporter);
        $before = UploadedFile::fake()->image('existing-before.jpg', 24, 16);
        $after = UploadedFile::fake()->image('existing-after.png', 20, 12);
        $beforeBytes = file_get_contents($before->getRealPath());
        $afterBytes = file_get_contents($after->getRealPath());
        $problem->photo_before = 'existing-before.jpg';
        $problem->photo_after = 'existing-after.png';
        $problem->save();
        Storage::disk('public')->put('Problem_Report_File/'.$problem->photo_before, $beforeBytes);
        Storage::disk('public')->put('Problem_Report_File/'.$problem->photo_after, $afterBytes);
        $admin = User::factory()->create(['role_id' => 1]);

        $this->actingAs($admin)->post('/problemReport/'.$problem->id.'/updateStatus', $filePayload + [
            'status' => $status, 'result_desc' => 'Status changed without replacing evidence',
        ])->assertRedirect('/problemReport')->assertSessionHas('success')->assertSessionMissing('error');

        $this->assertDatabaseHas('problem_report', [
            'id' => $problem->id, 'status' => $status,
            'result_desc' => 'Status changed without replacing evidence',
            'photo_before' => 'existing-before.jpg', 'photo_after' => 'existing-after.png',
        ]);
        $this->assertSame($beforeBytes, Storage::disk('public')->get('Problem_Report_File/existing-before.jpg'));
        $this->assertSame($afterBytes, Storage::disk('public')->get('Problem_Report_File/existing-after.png'));
        $this->assertCount(2, Storage::disk('public')->allFiles());
    }

    public function test_missing_problem_bindings_return_not_found(): void
    {
        foreach (['editStatus', 'editStatusClient'] as $action) {
            $this->get('/problemReport/999/'.$action)->assertNotFound();
        }
        foreach (['updateStatus', 'updateStatusClient'] as $action) {
            $this->post('/problemReport/999/'.$action, [])->assertNotFound();
        }
    }

    private function reportFixture(User $user, array $attributes = []): ProblemReport
    {
        return ProblemReport::create(array_merge([
            'user_id' => $user->id, 'date' => '2026-09-13 10:00:00',
            'pr_category_id' => 1, 'description' => 'CRUD scoped fixture',
        ], $attributes));
    }
}
