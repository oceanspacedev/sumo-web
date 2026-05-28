<?php

namespace App\Providers;

use App\Models\RequestBarang;
use App\Models\ProblemReport;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapThree();

        View::composer('*', function ($view) {
            $userDivisi = Auth::user()->division_id ?? '';
            
            ##BADGE REQUEST
            ##BADGE ACCOUNTING
            $notifRequestAcc = RequestBarang::with('user', 'request_approval')
            ->whereHas('request_approval', function ($query) {
                $query->where('approval_type', 'ACCOUNTING')
                ->where('approved_by', null);
            })
            ->where('status_client', '!=', 2)
            ->where('request_type_id', 1)
            ->count();

            if($notifRequestAcc == '0') {
                $notifRequestAcc = '';
            }

            ##BADGE ADMIN
            $notifRequestAdmin = RequestBarang::with('user', 'request_approval')
            ->whereHas('request_approval', function ($query) {
                $query->where('approval_type', 'EXECUTOR')
                ->where('approved_by', null);
            })
            ->where('status_client', '!=', 2)
            ->count();
            
            if($notifRequestAdmin == '0') {
                $notifRequestAdmin = '';
            }

            ##BADGE APPROVAL
            $notifRequestApprov = RequestBarang::with('user', 'request_approval')
            ->whereHas('request_type', function($q) use($userDivisi) { $q->where('pic_division_id', $userDivisi); })
            ->whereHas('request_approval', function ($query) {
                $query->where('approval_type', 'EXECUTOR')
                ->where('approved_by', null);
            })
            ->where('status_client', '!=', 2)
            ->count();
            
            //BADGE DI WHM EXECUTOR
            if ($userDivisi == 9) {
                $notifRequestApprov = RequestBarang::with('user.division.area', 'request_approval')
                ->whereHas('user.division.area', function ($query) {
                    $query->whereIn('area_id', [4,5,14,19]);
                })
                ->whereHas('request_approval', function ($query) {
                    $query->where('approval_type', 'MANAGER')
                    ->where('approved_by', null);
                })
                ->where('status_client', '!=', 2)
                ->where('request_type_id', 2)
                ->count();
            }

            //BADGE DI AUDIT EXECUTOR
            if ($userDivisi == 12) {
                $notifRequestApprov = RequestBarang::with('user.division.area', 'request_approval')
                ->whereHas('user.division.area', function ($query) {
                    $query->whereIn('area_id', [3,4,5]);
                })
                ->whereHas('request_approval', function ($query) {
                    $query->where('approval_type', 'MANAGER')
                    ->where('approved_by', null);
                })
                ->where('status_client', '!=', 2)
                ->where('request_type_id', 3)
                ->count();
            }

            if($notifRequestApprov == '0') {
                $notifRequestApprov = '';
            }

            ##BADGE ENDUSER
            if (Auth::check()){
                $notifRequestUser = RequestBarang::with('user', 'request_approval')
                ->where('user_id', Auth::id())
                ->where('status_client', '!=', 2)
                ->whereHas('request_approval', function ($query) {
                    $query->where('approval_type', 'ENDUSER')
                    ->where('approved_by', null);
                })
                ->count();

                if($notifRequestUser == '0') {
                    $notifRequestUser = '';
                }
            } else {
                $notifRequestUser = '';
            }

            ## BADGE PROBLEM REPORT
            ##KALAU DIVISI HCM TAMPILIN NOTIF REPORT YANG GANGGUAN UMUM AJA
            if ($userDivisi == 6) {
                $notifReportAdmin = ProblemReport::where('closed_by', null)
                ->where('pr_category_id', 7)
                ->count();
            } else {
                $notifReportAdmin = ProblemReport::where('closed_by', null)
                ->where('pr_category_id', '!=', 7)
                ->count();
            }

            if (Auth::id() == 1) {
                $notifReportAdmin = ProblemReport::where('closed_by', null)
                ->count();
            }

            if($notifReportAdmin == '0') {
                $notifReportAdmin = '';
            }

            if (Auth::check()){
            $notifReportUser = ProblemReport::where('user_id', Auth::id())->where('status_client', 0)->count();
                if($notifReportUser == '0') {
                    $notifReportUser = '';
                }
            } else {
                $notifReportUser = '';
            }

            if(Auth::check() && Auth::user()->role_id > 3) {
                $requestApprove = RequestBarang::with('user', 'request_type', 'request_approval.user')
                ->whereHas('request_approval', function ($query) {
                    $query->where('approval_type', 'EXECUTOR')
                    ->where('approved_by', '!=', null);
                })
                ->whereHas('request_approval', function ($query) {
                    $query->where('approval_type', 'ENDUSER')
                    ->where('approved_by', null);
                })
                ->where('user_id', Auth::id())
                ->where('status_client', '!=', 2)
                ->get();

                $problemApprove = ProblemReport::with('prcategory', 'closedby')
                ->where('user_id', Auth::id())
                ->where('closed_by', '!=', null)
                ->where('status_client', 0)
                ->get();
                
                $totalNotif = $requestApprove->where('status_client', '!=', 2)->count() + $problemApprove->count();

            } else {
                $requestApprove = [];
                $problemApprove = [];
                $totalNotif = 0;
            }


            return $view->with([
                'notifRequestAcc' => $notifRequestAcc,
                'notifRequestAdmin' => $notifRequestAdmin,
                'notifRequestApprov' => $notifRequestApprov,
                'notifRequestUser' => $notifRequestUser,
                'notifReportAdmin' => $notifReportAdmin,
                'notifReportUser' => $notifReportUser,
                'requestApprove' => $requestApprove,
                'problemApprove' => $problemApprove,
                'totalNotif' => $totalNotif,
            ]);
        });
    }
}
