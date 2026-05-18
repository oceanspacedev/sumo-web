<?php

namespace App\Console\Commands;

use App\Models\RequestBarang;
use App\Models\RequestApproval;
use App\Models\ProblemReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AutoApprove extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autoapprove';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is command to auto approve status_client on table Request & ProblemReport';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ##AUTO APPROVE PROBLEM REPORT
        $date24HoursAgo = Carbon::now()->subHours(24)->toDateTimeString();

        $problems = DB::table('problem_report')
        ->where('closed_by','!=', null)
        ->where('status_client', 0)
        ->whereDate('closed_at', '<', $date24HoursAgo)
        ->get();
        
        foreach ($problems as $problem) {
            DB::table('problem_report')
            ->where('id', $problem->id)
            ->update(['status_client' => 1]);
        }

        return $this->info('Successfully approved !');
    }
}
