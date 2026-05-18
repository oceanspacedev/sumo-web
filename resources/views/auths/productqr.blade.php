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
				<div class="auth-box" style="height: 1000px; !important">
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
											<h5><strong>Scan QR Barang</strong></h5>
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Nama Barang :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="product" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Tipe Unit :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="unittype" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Harga :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="price" class="form-control">
										</div>
									</div>
								</div>
								<div class="form-group">
									<div class="row">
										<div class="col-md-4">
											<h5><strong>Kategori :</strong></h5>
										</div>
										<div class="col-md-8">
											<input type="text" id="category" class="form-control">
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
	<script>
		function onScanSuccess(decodedText, decodedResult) {
			// handle the scanned code as you like, for example:
			// console.log(`Code matched = ${decodedText}`, decodedResult);
			$("#result").val(decodedText);
			console.log(decodedText);

			$('#loading').show();

			// Send AJAX request to search data
			$.ajax({
				url: '/search-product',
				method: 'POST',
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				data: {
					id: decodedText
				},
				success: function(response) {
					// Handle the response from the server
					console.log(response);

					if (response.length > 0) {
						var data = response[0];

						$('#product').val(data.product);
						$('#category').val(data.category.category);
						$('#unittype').val(data.unit_type.unit_type);
						$('#price').val(data.price);
					}
					$('#loading').hide();
				},
				error: function(xhr, status, error) {
					console.error(xhr.status + ': ' + xhr.statusText);
					console.error(error);

					 $('#loading').hide();
				}
			});
		}
		
		function onScanFailure(error) {
			// handle scan failure, usually better to ignore and keep scanning.
			// for example:
				console.warn(`Code scan error = ${error}`);
			}
			
		let html5QrcodeScanner = new Html5QrcodeScanner(
			"reader",
			{ fps: 10, qrbox: {width: 250, height: 250} },
			/* verbose= */ false);
			html5QrcodeScanner.render(onScanSuccess, onScanFailure);
	</script>
	<script src="{{ asset('/sw.js') }}"></script>
	<script>
		if (!navigator.serviceWorker.controller) {
			navigator.serviceWorker.register("/sw.js").then(function (reg) {
				console.log("Service worker has been registered for scope: " + reg.scope);
			});
		}
	</script>
</body>
</html>
