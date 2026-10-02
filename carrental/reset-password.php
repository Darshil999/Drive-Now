<?php
include('includes/config.php');

// The token arrives in the emailed link (?token=...) and is re-posted with the form.
$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$resetEmail = find_password_reset_email($dbh, $token);

if (isset($_POST['setpassword'])) {
    if ($resetEmail === null) {
        flash('error', 'This reset link is invalid or has expired. Please request a new one.');
        redirect('index.php');
    }
    $problem = password_problem($_POST['newpassword'] ?? '', $_POST['confirmpassword'] ?? '');
    if ($problem) {
        flash('error', $problem);
        redirect('reset-password.php?token=' . urlencode($token));
    }
    if (complete_password_reset($dbh, $token, $_POST['newpassword'])) {
        flash('success', 'Your password has been changed. You can now log in.');
    } else {
        flash('error', 'This reset link is invalid or has expired. Please request a new one.');
    }
    redirect('index.php');
}
?>
<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="referrer" content="no-referrer">
<title>DriveNow | Reset Password</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
<link rel="stylesheet" href="assets/css/style.css" type="text/css">
<link href="assets/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/switcher/css/red.css" type="text/css">
<link rel="stylesheet" href="assets/css/drivenow.css" type="text/css">
<link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
</head>
<body>

<?php include('includes/header.php');?>

<section class="page-header profile_page">
  <div class="container">
    <div class="page-header_wrap">
      <div class="page-heading">
        <h1>Reset Password</h1>
      </div>
      <ul class="coustom-breadcrumb">
        <li><a href="index.php">Home</a></li>
        <li>Reset Password</li>
      </ul>
    </div>
  </div>
  <div class="dark-overlay"></div>
</section>

<section class="section-padding">
  <div class="container">
    <div class="row">
      <div class="col-md-6 col-md-offset-3">
        <div class="profile_wrap">
        <?php if ($resetEmail === null) { ?>
          <div class="empty-state">
            <i class="fa fa-unlink" aria-hidden="true"></i>
            <p>This reset link is invalid, has already been used, or has expired.</p>
            <a href="#forgotpassword" class="btn" data-toggle="modal">Request a new link</a>
          </div>
        <?php } else { ?>
          <h5 class="uppercase underline">Choose a new password</h5>
          <p>Resetting the password for <strong><?php echo e($resetEmail); ?></strong>.</p>
          <form method="post" action="reset-password.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="token" value="<?php echo e($token); ?>">
            <div class="form-group">
              <label class="control-label" for="newpassword">New Password</label>
              <input class="form-control white_bg" id="newpassword" type="password" name="newpassword" minlength="<?php echo MIN_PASSWORD_LENGTH; ?>" autocomplete="new-password" required>
              <small class="help-block">At least <?php echo MIN_PASSWORD_LENGTH; ?> characters, including a letter and a number.</small>
            </div>
            <div class="form-group">
              <label class="control-label" for="confirmpassword">Confirm Password</label>
              <input class="form-control white_bg" id="confirmpassword" type="password" name="confirmpassword" autocomplete="new-password" required>
            </div>
            <div class="form-group">
              <input type="submit" value="Set New Password" name="setpassword" class="btn btn-block">
            </div>
          </form>
        <?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php');?>
<?php include('includes/login.php');?>
<?php include('includes/registration.php');?>
<?php include('includes/forgotpassword.php');?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/interface.js"></script>
</body>
</html>
