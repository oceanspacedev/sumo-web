<?php

namespace Tests\Concerns;

use App\Models\Product;
use App\Models\RequestApproval;
use App\Models\RequestBarang;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;

trait ExercisesBusinessCompatibility
{
    use RefreshDatabase;

    protected User $applicant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 9, 13)->setTime(12, 30));
        Storage::fake('public');
        DB::table('areas')->insert(['id' => 4, 'area' => 'Fixture Area']);
        DB::table('divisions')->insert(['id' => 6, 'division' => 'Fixture Division', 'area_id' => 4]);
        foreach ([1, 2, 3] as $type) {
            DB::table('request_type')->insert([
                'id' => $type, 'request_type' => 'Fixture '.$type, 'pic_division_id' => 6,
            ]);
        }
        $this->applicant = User::factory()->create(['role_id' => 4, 'division_id' => 6]);
        $this->product = Product::create([
            'product' => 'Fixture stationery', 'category_id' => 1, 'unit_type_id' => 1,
            'price' => 12500, 'stock' => 100,
        ]);
        $this->actingAs($this->applicant);
    }

    public static function openRequestCases(): array
    {
        $cases = [];
        foreach ([1, 2, 3] as $type) {
            foreach ([0, 3, 4] as $status) {
                $cases["type {$type}, open status {$status}"] = [$type, $status];
            }
        }

        return $cases;
    }

    #[DataProvider('openRequestCases')]
    public function test_two_open_requests_are_allowed_per_user_and_type(int $type, int $status): void
    {
        $other = User::factory()->create();
        $this->requestFixture(['request_type_id' => $type, 'status_client' => $status]);
        foreach ([1, 2] as $finishedStatus) {
            $this->requestFixture(['request_type_id' => $type, 'status_client' => $finishedStatus]);
            $this->requestFixture(['request_type_id' => $type, 'user_id' => $other->id]);
        }
        $this->requestFixture(['request_type_id' => $type])->delete();
        $this->requestFixture(['request_type_id' => $type === 1 ? 2 : 1]);

        $before = RequestBarang::count();
        $this->post('/request/store', $this->requestPayload($type))
            ->assertRedirect('/request')->assertSessionHas('success')->assertSessionMissing('error');
        $this->assertSame($before + 1, RequestBarang::count());

        $created = RequestBarang::latest('id')->firstOrFail();
        $this->assertSame('REQ'.$created->id, $created->request_code);
        $this->assertSame(['ACCOUNTING', 'MANAGER', 'EXECUTOR', 'ENDUSER'],
            $created->request_approval()->orderBy('id')->pluck('approval_type')->all());
        $this->assertSame(0, $created->request_approval()->whereNotNull('approved_by')->count());
        $this->assertEquals(0, $created->total_cost);
        $this->assertEquals(0, $created->status_client);
        $this->assertEquals(4, $created->request_detail()->first()->qty_request);
        $this->assertNull($created->request_detail()->first()->qty_approved);

        $this->flushSession();
        $this->post('/request/store', $this->requestPayload($type))
            ->assertRedirect('/request')->assertSessionHas('error',
                'Harap menunggu hingga pengajuan diproses dan status akhir diselesaikan !');
        $this->assertSame($before + 1, RequestBarang::count());
        $this->assertEquals(100, $this->product->fresh()->stock);
    }

    public function test_approval_stages_preserve_zero_quantity_costs_and_stock(): void
    {
        $payload = $this->requestPayload();
        $payload['products'] = [$this->product->id, $this->product->id, $this->product->id];
        $payload['qty_requests'] = [4, 2, 1.5];
        $payload['descriptions'] = ['Default quantity', 'Explicit rejection', 'Partial approval'];
        $this->post('/request/store', $payload)->assertSessionHas('success');
        $request = RequestBarang::latest('id')->firstOrFail();
        [$default, $zero, $partial] = $request->request_detail()->orderBy('id')->get()->all();
        $manager = User::factory()->create(['role_id' => 3, 'division_id' => 6]);
        $accountant = User::factory()->create(['role_id' => 2]);
        $executor = User::factory()->create(['role_id' => 3, 'division_id' => 6]);

        $this->actingAs($accountant)->post('/request/'.$request->id.'/updateStatusAcc', ['status_po' => 1])
            ->assertSessionHas('success');
        $this->assertEquals(1, $request->fresh()->status_po);
        $this->assertApproval($request, 'ACCOUNTING', $accountant);

        $this->actingAs($manager)->post('/request/'.$zero->id.'/updateRequest', [
            'request_id' => $request->id, 'qty_approved' => '0',
        ])->assertSessionHas('success');
        $this->post('/request/'.$partial->id.'/updateRequest', [
            'request_id' => $request->id, 'qty_approved' => '0.5',
        ])->assertSessionHas('success');

        $this->get('/request/'.$request->id)->assertOk()
            ->assertViewHas('grandTotal', 56250)->assertSee('Total Biaya : Rp 56.250');
        $this->post('/fixRequest/'.$request->id)->assertSessionHas('success');
        $this->assertEquals(4, $default->fresh()->qty_approved);
        $this->assertEquals(0, $zero->fresh()->qty_approved);
        $this->assertEquals(0.5, $partial->fresh()->qty_approved);
        $this->assertApproval($request, 'MANAGER', $manager);
        $this->post('/fixRequest/'.$request->id)->assertSessionHas('success');
        $this->assertSame(4, $request->request_approval()->count());
        $this->get('/request/'.$request->id)->assertOk()->assertViewHas('grandTotal', 56250);

        $this->actingAs($executor)->post('/request/'.$request->id.'/updateStatus', [
            'status' => 'CLOSED', 'notes' => 'Delivered',
        ])->assertSessionHas('success');
        $this->assertEquals(4, $request->fresh()->status_client);
        $this->assertApproval($request, 'EXECUTOR', $executor);
        $this->actingAs($this->applicant)->post('/request/'.$request->id.'/updateStatusClient', [
            'status_client' => 1, 'user_notes' => 'Received',
        ])->assertSessionHas('success');
        $this->assertApproval($request, 'ENDUSER', $this->applicant);
        $this->assertEquals(1, $request->fresh()->status_client);
        $this->assertSame('Received', $request->fresh()->user_notes);
        // The original application computes display totals without mutating stock or stored cost.
        $this->assertEquals(0, $request->fresh()->total_cost);
        $this->assertEquals(100, $this->product->fresh()->stock);
    }

    public function test_executor_statuses_and_user_cancellation_preserve_details(): void
    {
        $this->post('/request/store', $this->requestPayload())->assertSessionHas('success');
        $request = RequestBarang::latest('id')->firstOrFail();
        $originalDetails = $request->request_detail()->get()->toArray();
        $executor = User::factory()->create(['role_id' => 3, 'division_id' => 6]);
        foreach (['PROCESSED' => 3, 'PENDING' => 0, 'CANCELLED' => 2] as $status => $clientStatus) {
            $this->actingAs($executor)->post('/request/'.$request->id.'/updateStatus', [
                'status' => $status, 'notes' => $status,
            ])->assertSessionHas('success');
            $this->assertEquals($clientStatus, $request->fresh()->status_client);
            $approval = $request->request_approval()->where('approval_type', 'EXECUTOR')->firstOrFail();
            $this->assertEquals($status === 'PENDING' ? null : $executor->id, $approval->approved_by);
            $this->assertSame($status === 'PENDING', $approval->approved_at === null);
        }
        $this->actingAs($this->applicant)->get('/request/'.$request->id.'/cancelRequest')
            ->assertRedirect('/request')->assertSessionHas('success');
        $this->assertEquals(2, $request->fresh()->status_client);
        $this->assertSame($originalDetails, $request->request_detail()->get()->toArray());
        $this->assertEquals(100, $this->product->fresh()->stock);
        $this->assertEquals(0, $request->fresh()->total_cost);
    }

    public function test_waiting_end_user_only_updates_notes(): void
    {
        $request = $this->requestFixture(['status_client' => 3]);
        $this->post('/request/'.$request->id.'/updateStatusClient', [
            'status_client' => 0, 'user_notes' => 'Still waiting',
        ])->assertSessionHas('success');
        $this->assertEquals(3, $request->fresh()->status_client);
        $this->assertSame('Still waiting', $request->fresh()->user_notes);
        $this->assertSame(0, $request->request_approval()->where('approval_type', 'ENDUSER')->count());
    }

    public function test_autoapprove_retains_date_cutoff_and_leaves_requests_unchanged(): void
    {
        $request = $this->requestFixture(['status_client' => 0, 'closed_by' => $this->applicant->id,
            'closed_at' => '2026-09-01 10:00:00']);
        foreach ([
            [1, '2026-09-11 23:59:59', 1, 0],
            [2, '2026-09-12 00:00:00', 1, 0],
            [3, '2026-09-12 23:59:59', 1, 0],
            [4, '2026-09-13 00:00:00', 1, 0],
            [5, '2026-09-01 00:00:00', null, 0],
            [6, '2026-09-01 00:00:00', 1, 2],
            [7, null, 1, 0],
        ] as [$id, $closedAt, $closedBy, $status]) {
            DB::table('problem_report')->insert([
                'id' => $id, 'date' => '2026-09-01 00:00:00', 'user_id' => $this->applicant->id,
                'pr_category_id' => 1, 'closed_at' => $closedAt, 'closed_by' => $closedBy,
                'status_client' => $status,
            ]);
        }

        $this->artisan('autoapprove')->expectsOutput('Successfully approved !')->assertSuccessful();
        $this->assertSame([1 => 1, 2 => 1, 3 => 1, 4 => 0, 5 => 0, 6 => 2, 7 => 0],
            DB::table('problem_report')->orderBy('id')->pluck('status_client', 'id')->all());
        $this->assertEquals(0, $request->fresh()->status_client);

        $events = collect(app(Schedule::class)->events())->filter(
            fn ($event) => str_contains($event->command ?? '', 'autoapprove')
        );
        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('* * * * *', $event->expression);
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame(storage_path('logs/scheduler.log'), $event->output);
        $this->assertTrue($event->shouldAppendOutput);
    }

    private function requestFixture(array $attributes = []): RequestBarang
    {
        return RequestBarang::create(array_merge([
            'user_id' => $this->applicant->id, 'date' => now(), 'total_cost' => 0,
            'status_client' => 0, 'request_type_id' => 2,
        ], $attributes));
    }

    private function requestPayload(int $type = 2): array
    {
        $payload = [
            'user_id' => $this->applicant->id, 'request_type_id' => $type,
            'products' => [$this->product->id], 'qty_requests' => [4],
            'qty_remainings' => [null], 'descriptions' => ['Fixture request'],
        ];
        if ($type !== 2) {
            $payload['request_file'] = UploadedFile::fake()->create('approval.pdf', 10, 'application/pdf');
        }

        return $payload;
    }

    private function assertApproval(RequestBarang $request, string $stage, User $actor): void
    {
        $approval = RequestApproval::where('request_id', $request->id)
            ->where('approval_type', $stage)->sole();
        $this->assertEquals($actor->id, $approval->approved_by);
        $this->assertSame(now()->format('Y-m-d H:i:s'), $approval->approved_at);
    }
}
