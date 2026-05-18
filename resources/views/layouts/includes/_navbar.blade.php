<nav class="navbar navbar-default navbar-fixed-top">
	<div class="brand">
		<a href="/dashboard"><img src="{{asset('admin/assets/img/logo-sumo.png')}}" alt="Klorofil Logo" class="img-responsive logo"></a>
	</div>
	<div class="container-fluid">
		<div class="navbar-btn">
			<button type="button" class="btn-toggle-fullwidth"><i class="lnr lnr-menu"></i></button>
		</div>
		<div class="navbar-btn navbar-btn-right">
			<ul class="nav">
				<li class="dropdown">
					<a class="btn btn-default update-pro dropdown-toggle" data-toggle="dropdown"><i class="fa fa-user"></i> <span>{{auth()->user()->fullname}}   </span><i class="icon-submenu lnr lnr-chevron-down"></i></a>
					<ul class="dropdown-menu">
						<li><a href="/logout"><i class="lnr lnr-exit"></i> <span>KELUAR</span></a></li>
					</ul>
				</li>
			</ul>
		</div>
		@if(auth()->user()->role_id > 3)
		<div id="navbar-menu">
			<ul class="nav navbar-nav navbar-right">
				<li class="dropdown">
					<a href="#" class="dropdown-toggle icon-menu" data-toggle="dropdown">
						<i class="lnr lnr-alarm"></i>
						<span class="badge bg-danger">{{ $totalNotif }}</span>
					</a>
					<ul class="dropdown-menu notifications">
						@if ($requestApprove->where('status_client', '==', 4)->count() > 0)
							@foreach ($requestApprove as $ra) 
								@foreach ($ra->request_approval as $ral)
									@if ($ral->approval_type == 'EXECUTOR' && $ral->approved_by != null)
										<li><a href="#" class="notification-item"><span class="dot bg-success"></span>Pengajuan {{ $ra->request_type->request_type }} telah diproses oleh {{$ral->user->fullname}}</a></li>
									@endif
								@endforeach
							@endforeach
						@else
							<li><a href="#" class="notification-item"><span class="dot bg-danger"></span>Belum ada pengajuan yang diproses</a></li>
						@endif
						@if ($problemApprove->where('closed_by', '!=', null)->count() > 0)
							@foreach ($problemApprove as $pa) 
								<li><a href="#" class="notification-item"><span class="dot bg-success"></span>Laporan {{ $pa->prcategory->problem_report_category }} telah diproses oleh {{$pa->closedby->fullname}}</a></li>
							@endforeach
						@else
							<li><a href="#" class="notification-item"><span class="dot bg-danger"></span>Belum ada laporan yang diproses</a></li>
						@endif
					</ul>
				</li>
			</ul>
		</div>
		@endif
	</div>
</nav>