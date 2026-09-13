<!doctype html>
<html lang="en" class="fullscreen-bg">

<head>
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<!-- PWA  -->
	<meta name="theme-color" content="#090089"/>
	<link rel="manifest" href="{{ asset('/manifest.json') }}">
	<title>LOGIN SUMO | by Business Development</title>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<!-- VENDOR CSS -->
	<link rel="stylesheet" href="{{asset('admin/assets/css/bootstrap.min.css')}}">
	<link rel="stylesheet" href="{{asset('admin/assets/vendor/font-awesome/css/font-awesome.min.css')}}">
	<link rel="stylesheet" href="{{asset('admin/assets/vendor/linearicons/style.css')}}">
	<!-- MAIN CSS -->
	<link rel="stylesheet" href="{{asset('admin/assets/css/main.css')}}">
	<!-- FOR DEMO PURPOSES ONLY. You should remove this in your project -->
	<link rel="stylesheet" href="{{asset('admin/assets/css/demo.css')}}">
	<!-- GOOGLE FONTS -->
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700" rel="stylesheet">
	<!-- ICONS -->
	<link rel="apple-touch-icon" sizes="76x76" href="{{asset('admin/assets/img/gais-block.png')}}">
	<link rel="icon" type="image/png" sizes="96x96" href="{{asset('admin/assets/img/gais-block.png')}}">
	<style>
    .loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        animation: spin 1s linear infinite;
        margin: 0 auto;
        margin-top: 20px;
        display: none; /* Hide initially */
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>

<body>
	<!-- WRAPPER -->
	<div id="wrapper">
		<div class="vertical-align-wrap">
			<div class="vertical-align-middle">
				<div class="auth-box" style="height: 1400px !important">
					<div class="left">
						<div class="content">
							<div class="header">
								<div class="logo text-center"><img src="{{asset('admin/assets/img/logo-sumo.png')}}" alt="Klorofil Logo"></div>
							</div>
							<div id="reader" width="80px" style="margin-bottom: 20px;"></div>	
							<div id="loading" class="loader"></div>
							<div class="col-md-12">
								<div class="form-group">
									<div class="row">
										<div class="col-md-12">
											<h5><strong>Scan QR Pengajuan</strong></h5>
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Barang :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="product" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Jml Request :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="qty_request" class="form-control">
										</div>
									</div>
								</div>
								<!-- <div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Jml Sisa :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="qty_remaining" class="form-control">
										</div>
									</div>
								</div> -->
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Jml Approved :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="qty_approved" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Keterangan :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="description" class="form-control">
										</div>
									</div>
								</div>
								<hr>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Pemohon :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="name" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Tanggal :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="date" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Status PO :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="status_po" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Diproses oleh :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="closed_by" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Diproses pada :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="closed_at" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Status Client :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="status_client" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Catatan GA :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="notes" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Catatan User :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="user_notes" class="form-control">
										</div>
									</div>
								</div>
							</div>				
						</div>
					</div>
					<div class="right">
						<div class="overlay"></div>
						<div class="content text">
							<h1 class="heading">Submission Mobile</h1>
							<p>by Business Development</p>
						</div>
					</div>
					<div class="clearfix"></div>
				</div>
			</div>
		</div>
	</div>
	<!-- END WRAPPER -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
	<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
	@vite(['resources/js/pwa.js', 'resources/js/scanqr.js'])
</body>
</html>
