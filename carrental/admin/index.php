<?php
include('includes/config.php');
if (!empty($_SESSION['alogin'])) {
    redirect('dashboard.php');
}
if(isset($_POST['login']))
{
$adminName = attempt_admin_login($dbh, $_POST['username'] ?? '', $_POST['password'] ?? '');
if ($adminName !== null)
{
session_regenerate_id(true);
$_SESSION['alogin'] = $adminName;
redirect('dashboard.php');
} else {
flash('error', 'Invalid username or password.');
redirect('index.php');
}
}

?>
<!doctype html>
<html lang="en" class="no-js">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	<title>DriveNow Admin | Login</title>
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="css/bootstrap-social.css">
	<link rel="stylesheet" href="css/bootstrap-select.css">
	<link rel="stylesheet" href="css/fileinput.min.css">
	<link rel="stylesheet" href="css/awesome-bootstrap-checkbox.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/drivenow-admin.css">
</head>

<body>
	
	<div class="login-page bk-img" style="background-image: url(img/login-bg.jpg);">
		<div class="form-content">
			<div class="container">
				<div class="row">
					<div class="col-md-6 col-md-offset-3">
						<h1 class="text-center text-bold mt-4x" style="color:#fff">DriveNow Admin</h1>
						<div class="well row pt-2x pb-3x bk-light">
							<div class="col-md-8 col-md-offset-2">
								<form method="post">
<?php echo csrf_field(); ?>

									<?php render_flashes(); ?>
									<label for="admin-username" class="text-uppercase text-sm">Your Username </label>
									<input type="text" placeholder="Username" id="admin-username" name="username" class="form-control mb" autocomplete="username" required>

									<label for="admin-password" class="text-uppercase text-sm">Password</label>
									<input type="password" placeholder="Password" id="admin-password" name="password" class="form-control mb" autocomplete="current-password" required>
		

									<button class="btn btn-primary btn-block" name="login" type="submit">LOGIN</button>

								</form>

			<p style="margin-top: 4%" align="center"><a href="../index.php">Back to Home</a>	</p>
							</div>

						</div>
							
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Loading Scripts -->
	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap-select.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.dataTables.min.js"></script>
	<script src="js/dataTables.bootstrap.min.js"></script>
	<script src="js/Chart.min.js"></script>
	<script src="js/fileinput.js"></script>
	<script src="js/chartData.js"></script>
	<script src="js/main.js"></script>

</body>

</html>