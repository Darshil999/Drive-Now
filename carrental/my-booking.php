<?php
include('includes/config.php');
if (!is_logged_in()) {
    flash('info', 'Please log in to view your bookings.');
    redirect('index.php');
}

$useremail = $_SESSION['login'];
$today = date('Y-m-d');

// Customers can cancel their own pending/confirmed bookings before the pick-up date.
if (isset($_POST['cancel_booking'])) {
    if (cancel_customer_booking($dbh, $_POST['booking_id'] ?? 0, $useremail, $today)) {
        flash('success', 'Your booking has been cancelled.');
    } else {
        flash('error', 'This booking can no longer be cancelled.');
    }
    redirect('my-booking.php');
}

$userStmt = $dbh->prepare('SELECT FullName, Address, City, Country FROM tblusers WHERE EmailId = :email');
$userStmt->execute([':email' => $useremail]);
$user = $userStmt->fetch(PDO::FETCH_OBJ);

$bookingStmt = $dbh->prepare('SELECT tblbooking.id, tblbooking.BookingNumber, tblbooking.FromDate, tblbooking.ToDate,
        tblbooking.message, tblbooking.Status, tblbooking.PostingDate,
        tblvehicles.id AS vid, tblvehicles.Vimage1, tblvehicles.VehiclesTitle, tblvehicles.PricePerDay, tblbrands.BrandName
    FROM tblbooking
    JOIN tblvehicles ON tblbooking.VehicleId = tblvehicles.id
    JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand
    WHERE tblbooking.userEmail = :email
    ORDER BY tblbooking.FromDate DESC, tblbooking.id DESC');
$bookingStmt->execute([':email' => $useremail]);
$bookings = $bookingStmt->fetchAll(PDO::FETCH_OBJ);

// Group bookings for the Upcoming / Past / Cancelled tabs.
$filter = $_GET['show'] ?? 'all';
$counts = ['all' => count($bookings), 'upcoming' => 0, 'past' => 0, 'cancelled' => 0];
foreach ($bookings as $b) {
    if ((int) $b->Status === BOOKING_CANCELLED) {
        $b->group = 'cancelled';
    } elseif ($b->ToDate >= $today) {
        $b->group = 'upcoming';
    } else {
        $b->group = 'past';
    }
    $counts[$b->group]++;
}
if (!isset($counts[$filter])) {
    $filter = 'all';
}
?><!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>DriveNow | My Booking</title>
<!--Bootstrap -->
<link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
<!--Custome Style -->
<link rel="stylesheet" href="assets/css/style.css" type="text/css">
<!--OWL Carousel slider-->
<link rel="stylesheet" href="assets/css/owl.carousel.css" type="text/css">
<link rel="stylesheet" href="assets/css/owl.transitions.css" type="text/css">
<!--slick-slider -->
<link href="assets/css/slick.css" rel="stylesheet">
<!--bootstrap-slider -->
<link href="assets/css/bootstrap-slider.min.css" rel="stylesheet">
<!--FontAwesome Font Style -->
<link href="assets/css/font-awesome.min.css" rel="stylesheet">

<link rel="stylesheet" href="assets/switcher/css/red.css" type="text/css">
<link rel="stylesheet" href="assets/css/drivenow.css" type="text/css">
        
<!-- Fav and touch icons -->
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/favicon-icon/apple-touch-icon-144-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/favicon-icon/apple-touch-icon-72-precomposed.png">
<link rel="apple-touch-icon-precomposed" href="assets/images/favicon-icon/apple-touch-icon-57-precomposed.png">
<link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
<!-- Google-Font-->
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->  
</head>
<body>

        
<!--Header-->
<?php include('includes/header.php');?>
<!--Page Header-->
<!-- /Header --> 

<!--Page Header-->
<section class="page-header profile_page">
  <div class="container">
    <div class="page-header_wrap">
      <div class="page-heading">
        <h1>My Booking</h1>
      </div>
      <ul class="coustom-breadcrumb">
        <li><a href="index.php">Home</a></li>
        <li>My Booking</li>
      </ul>
    </div>
  </div>
  <!-- Dark Overlay-->
  <div class="dark-overlay"></div>
</section>
<!-- /Page Header--> 

<section class="user_profile inner_pages">
  <div class="container">
    <div class="user_profile_info gray-bg padding_4x4_40">
      <div class="upload_user_logo"> <img src="assets/images/dealer-logo.jpg" alt="Profile">
      </div>
      <div class="dealer_info">
        <h5><?php echo e($user->FullName ?? ''); ?></h5>
        <p><?php echo e($user->Address ?? ''); ?><br>
          <?php echo e(trim(($user->City ?? '') . ' ' . ($user->Country ?? ''))); ?></p>
      </div>
    </div>
    <div class="row">
      <div class="col-md-3 col-sm-3">
       <?php include('includes/sidebar.php');?>

      <div class="col-md-9 col-sm-9">
        <div class="profile_wrap">
          <h5 class="uppercase underline">My Bookings</h5>

          <ul class="nav nav-pills booking-tabs">
            <?php foreach (['all' => 'All', 'upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled'] as $key => $label) { ?>
              <li class="<?php echo $filter === $key ? 'active' : ''; ?>"><a href="my-booking.php?show=<?php echo $key; ?>"><?php echo $label; ?> <span class="badge"><?php echo $counts[$key]; ?></span></a></li>
            <?php } ?>
          </ul>

<?php
$shown = 0;
foreach ($bookings as $booking) {
    if ($filter !== 'all' && $booking->group !== $filter) {
        continue;
    }
    $shown++;
    $days = rental_days($booking->FromDate, $booking->ToDate);
    [$statusLabel, $statusClass] = booking_status_label($booking->Status);
    $canCancel = (int) $booking->Status !== BOOKING_CANCELLED && $booking->FromDate > $today;
?>
          <div class="booking-card">
            <div class="booking-card-head">
              <h4>Booking #<?php echo e($booking->BookingNumber); ?></h4>
              <span class="status-badge status-<?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
            </div>
            <div class="booking-card-body">
              <div class="vehicle_img">
                <a href="vehical-details.php?vhid=<?php echo (int) $booking->vid; ?>"><img src="admin/img/vehicleimages/<?php echo e($booking->Vimage1); ?>" alt="<?php echo e($booking->VehiclesTitle); ?>"></a>
              </div>
              <div class="booking-info">
                <h6><a href="vehical-details.php?vhid=<?php echo (int) $booking->vid; ?>"><?php echo e($booking->BrandName); ?>, <?php echo e($booking->VehiclesTitle); ?></a></h6>
                <p><i class="fa fa-calendar" aria-hidden="true"></i> <?php echo e(format_date($booking->FromDate)); ?> &rarr; <?php echo e(format_date($booking->ToDate)); ?></p>
                <p><small>Booked on <?php echo e(format_date($booking->PostingDate)); ?></small></p>
                <?php if ($booking->message !== null && $booking->message !== '') { ?>
                  <p><b>Message:</b> <?php echo e($booking->message); ?></p>
                <?php } ?>
                <?php if ($canCancel) { ?>
                  <form method="post" class="booking-actions" onsubmit="return confirm('Cancel this booking?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="booking_id" value="<?php echo (int) $booking->id; ?>">
                    <button type="submit" name="cancel_booking" class="btn btn-xs outline">Cancel booking</button>
                  </form>
                <?php } ?>
              </div>
            </div>
            <div class="booking-invoice">
              <table>
                <tr>
                  <th>Car</th>
                  <th>Days</th>
                  <th>Rent / Day</th>
                  <th>Total</th>
                </tr>
                <tr>
                  <td><?php echo e($booking->BrandName . ' ' . $booking->VehiclesTitle); ?></td>
                  <td><?php echo $days; ?></td>
                  <td><?php echo format_price($booking->PricePerDay); ?></td>
                  <td><strong><?php echo format_price($days * $booking->PricePerDay); ?></strong></td>
                </tr>
              </table>
            </div>
          </div>
<?php } ?>
<?php if ($shown === 0) { ?>
          <div class="empty-state">
            <i class="fa fa-car" aria-hidden="true"></i>
            <p>No bookings to show here yet.</p>
            <a href="car-listing.php" class="btn">Browse cars</a>
          </div>
<?php } ?>
        </div>
      </div>
    </div>
  </div>
</section>
<!--/my-vehicles-->
<?php include('includes/footer.php');?>

<!-- Scripts --> 
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script> 
<script src="assets/js/interface.js"></script> 
<!--bootstrap-slider-JS--> 
<script src="assets/js/bootstrap-slider.min.js"></script> 
<!--Slider-JS--> 
<script src="assets/js/slick.min.js"></script> 
<script src="assets/js/owl.carousel.min.js"></script>
</body>
</html>
