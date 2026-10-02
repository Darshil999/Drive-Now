<?php
include('includes/config.php');

$vhid = (int) ($_GET['vhid'] ?? 0);
$vehicleStmt = $dbh->prepare('SELECT tblvehicles.*, tblbrands.BrandName FROM tblvehicles JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand WHERE tblvehicles.id = :vhid');
$vehicleStmt->execute([':vhid' => $vhid]);
$vehicle = $vehicleStmt->fetch(PDO::FETCH_OBJ);
if (!$vehicle) {
    flash('error', 'Sorry, that vehicle could not be found.');
    redirect('car-listing.php');
}

$today = date('Y-m-d');

if (isset($_POST['submit'])) {
    $fromdate = $_POST['fromdate'] ?? '';
    $todate = $_POST['todate'] ?? '';
    $message = trim($_POST['message'] ?? '');

    $error = is_logged_in()
        ? booking_validation_error($fromdate, $todate, $message, $today)
        : 'Please log in to book a car.';

    if (!$error) {
        try {
            $bookingno = create_booking($dbh, $vhid, $_SESSION['login'], $fromdate, $todate, $message);
            $total = rental_days($fromdate, $todate) * $vehicle->PricePerDay;
            flash('success', 'Booking #' . $bookingno . ' received! Estimated total ' . format_price($total) . '. We will confirm it shortly.');
            redirect('my-booking.php');
        } catch (DomainException $ex) {
            $error = $ex->getMessage();
        } catch (PDOException $ex) {
            error_log('Booking failed: ' . $ex->getMessage());
            $error = 'Something went wrong while saving your booking. Please try again.';
        }
    }

    flash('error', $error);
    redirect('vehical-details.php?vhid=' . $vhid);
}

// Upcoming dates that are already taken (shown to the customer before booking).
$bookedStmt = $dbh->prepare('SELECT FromDate, ToDate FROM tblbooking
    WHERE VehicleId = :vhid AND Status <> :cancelled AND ToDate >= :today ORDER BY FromDate');
$bookedStmt->execute([':vhid' => $vhid, ':cancelled' => BOOKING_CANCELLED, ':today' => $today]);
$bookedRanges = $bookedStmt->fetchAll(PDO::FETCH_OBJ);
?>


<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>DriveNow | Vehicle Details</title>
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
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/images/favicon-icon/apple-touch-icon-144-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/images/favicon-icon/apple-touch-icon-72-precomposed.png">
<link rel="apple-touch-icon-precomposed" href="assets/images/favicon-icon/apple-touch-icon-57-precomposed.png">
<link rel="shortcut icon" href="assets/images/favicon-icon/favicon.png">
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
</head>
<body>


<!--Header-->
<?php include('includes/header.php');?>
<!-- /Header --> 

<!--Listing-Image-Slider-->

<?php $result = $vehicle; ?>

<section id="listing_img_slider">
  <?php foreach (['Vimage1', 'Vimage2', 'Vimage3', 'Vimage4', 'Vimage5'] as $imageField) {
    if (!empty($result->$imageField)) { ?>
  <div><img src="admin/img/vehicleimages/<?php echo e($result->$imageField);?>" class="img-responsive" alt="<?php echo e($result->BrandName . ' ' . $result->VehiclesTitle);?>" width="900" height="560"></div>
  <?php } } ?>
</section>
<!--/Listing-Image-Slider-->


<!--Listing-detail-->
<section class="listing-detail">
  <div class="container">
    <div class="listing_detail_head row">
      <div class="col-md-9">
        <h2><?php echo htmlentities($result->BrandName);?> , <?php echo htmlentities($result->VehiclesTitle);?></h2>
      </div>
      <div class="col-md-3">
        <div class="price_info">
          <p><?php echo format_price($result->PricePerDay);?> </p>Per Day
         
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-9">
        <div class="main_features">
          <ul>
          
            <li> <i class="fa fa-calendar" aria-hidden="true"></i>
              <h5><?php echo htmlentities($result->ModelYear);?></h5>
              <p>Reg.Year</p>
            </li>
            <li> <i class="fa fa-cogs" aria-hidden="true"></i>
              <h5><?php echo htmlentities($result->FuelType);?></h5>
              <p>Fuel Type</p>
            </li>
       
            <li> <i class="fa fa-user-plus" aria-hidden="true"></i>
              <h5><?php echo htmlentities($result->SeatingCapacity);?></h5>
              <p>Seats</p>
            </li>
          </ul>
        </div>
        <div class="listing_more_info">
          <div class="listing_detail_wrap">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs gray-bg" role="tablist">
              <li role="presentation" class="active"><a href="#vehicle-overview" aria-controls="vehicle-overview" role="tab" data-toggle="tab">Vehicle Overview</a></li>
              <li role="presentation"><a href="#accessories" aria-controls="accessories" role="tab" data-toggle="tab">Accessories</a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
              <div role="tabpanel" class="tab-pane active" id="vehicle-overview">
                <p><?php echo nl2br(e($result->VehiclesOverview));?></p>
              </div>

              <div role="tabpanel" class="tab-pane" id="accessories">
                <table>
                  <thead>
                    <tr>
                      <th colspan="2">Accessories</th>
                    </tr>
                  </thead>
                  <tbody>
<?php
$accessories = [
    'AirConditioner' => 'Air Conditioner',
    'AntiLockBrakingSystem' => 'AntiLock Braking System',
    'PowerSteering' => 'Power Steering',
    'PowerWindows' => 'Power Windows',
    'CDPlayer' => 'CD Player',
    'LeatherSeats' => 'Leather Seats',
    'CentralLocking' => 'Central Locking',
    'PowerDoorLocks' => 'Power Door Locks',
    'BrakeAssist' => 'Brake Assist',
    'DriverAirbag' => 'Driver Airbag',
    'PassengerAirbag' => 'Passenger Airbag',
    'CrashSensor' => 'Crash Sensor',
];
foreach ($accessories as $column => $label) { ?>
                    <tr>
                      <td><?php echo e($label); ?></td>
                      <td><i class="fa <?php echo $result->$column == 1 ? 'fa-check' : 'fa-close'; ?>" aria-label="<?php echo $result->$column == 1 ? 'Yes' : 'No'; ?>"></i></td>
                    </tr>
<?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!--Side-Bar-->
      <aside class="col-md-3">
        <div class="sidebar_widget booking-widget">
          <div class="widget_heading">
            <h5><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Book Now</h5>
          </div>
          <form method="post" id="booking-form" data-price="<?php echo (int) $result->PricePerDay; ?>" data-max-days="<?php echo MAX_RENTAL_DAYS; ?>" data-currency="<?php echo e(APP_CURRENCY); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
              <label for="fromdate">Pick-up date</label>
              <input type="date" class="form-control" id="fromdate" name="fromdate" min="<?php echo e($today); ?>" required>
            </div>
            <div class="form-group">
              <label for="todate">Return date</label>
              <input type="date" class="form-control" id="todate" name="todate" min="<?php echo e($today); ?>" required>
            </div>
            <div class="form-group">
              <label for="message">Message <small>(optional)</small></label>
              <textarea rows="3" class="form-control" id="message" name="message" maxlength="255" placeholder="Pick-up time, special requests..."></textarea>
            </div>
            <div class="booking-summary" id="booking-summary" aria-live="polite">
              Select dates to see the estimated total.
            </div>
          <?php if (is_logged_in()) { ?>
              <div class="form-group">
                <input type="submit" class="btn btn-block" name="submit" value="Book Now">
              </div>
          <?php } else { ?>
              <a href="#loginform" class="btn btn-block" data-toggle="modal" data-dismiss="modal">Login to Book</a>
          <?php } ?>
          </form>

          <div class="booked-dates">
            <h6><i class="fa fa-ban" aria-hidden="true"></i> Unavailable dates</h6>
            <?php if ($bookedRanges) { ?>
              <ul>
                <?php foreach ($bookedRanges as $range) { ?>
                  <li><?php echo e(format_date($range->FromDate)); ?> &ndash; <?php echo e(format_date($range->ToDate)); ?></li>
                <?php } ?>
              </ul>
            <?php } else { ?>
              <p>No upcoming bookings &mdash; this car is free on any date.</p>
            <?php } ?>
          </div>
        </div>
      </aside>
      <!--/Side-Bar-->
    </div>

    <div class="space-20"></div>
    <div class="divider"></div>

    <!--Similar-Cars-->
    <div class="similar_cars">
      <h3>Similar Cars</h3>
      <div class="row">
<?php
$similar = $dbh->prepare('SELECT tblvehicles.id, tblvehicles.VehiclesTitle, tblvehicles.PricePerDay, tblvehicles.FuelType, tblvehicles.ModelYear, tblvehicles.SeatingCapacity, tblvehicles.Vimage1, tblbrands.BrandName
    FROM tblvehicles JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand
    WHERE tblvehicles.VehiclesBrand = :bid AND tblvehicles.id <> :vhid LIMIT 4');
$similar->execute([':bid' => $vehicle->VehiclesBrand, ':vhid' => $vehicle->id]);
$similarCars = $similar->fetchAll(PDO::FETCH_OBJ);
if (!$similarCars) { ?>
        <div class="col-md-12"><p>No other cars from <?php echo e($vehicle->BrandName); ?> right now. <a href="car-listing.php">Browse all cars</a>.</p></div>
<?php }
foreach ($similarCars as $result) { ?>
        <div class="col-md-3 col-sm-6 grid_listing">
          <div class="product-listing-m gray-bg">
            <div class="product-listing-img"> <a href="vehical-details.php?vhid=<?php echo (int) $result->id;?>"><img src="admin/img/vehicleimages/<?php echo e($result->Vimage1);?>" class="img-responsive" alt="<?php echo e($result->VehiclesTitle);?>" /> </a>
            </div>
            <div class="product-listing-content">
              <h5><a href="vehical-details.php?vhid=<?php echo (int) $result->id;?>"><?php echo e($result->BrandName);?>, <?php echo e($result->VehiclesTitle);?></a></h5>
              <p class="list-price"><?php echo format_price($result->PricePerDay);?> / day</p>
              <ul class="features_list">
                <li><i class="fa fa-user" aria-hidden="true"></i><?php echo e($result->SeatingCapacity);?> seats</li>
                <li><i class="fa fa-calendar" aria-hidden="true"></i><?php echo e($result->ModelYear);?> model</li>
                <li><i class="fa fa-car" aria-hidden="true"></i><?php echo e($result->FuelType);?></li>
              </ul>
            </div>
          </div>
        </div>
<?php } ?>

      </div>
    </div>
    <!--/Similar-Cars--> 
    
  </div>
</section>
<!--/Listing-detail--> 

<!--Footer -->
<?php include('includes/footer.php');?>
<!-- /Footer--> 

<!--Back to top-->
<div id="back-top" class="back-top"> <a href="#top"><i class="fa fa-angle-up" aria-hidden="true"></i> </a> </div>
<!--/Back to top--> 

<!--Login-Form -->
<?php include('includes/login.php');?>
<!--/Login-Form --> 

<!--Register-Form -->
<?php include('includes/registration.php');?>

<!--/Register-Form --> 

<!--Forgot-password-Form -->
<?php include('includes/forgotpassword.php');?>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script> 
<script src="assets/js/interface.js"></script> 
<script src="assets/js/bootstrap-slider.min.js"></script> 
<script src="assets/js/slick.min.js"></script> 
<script src="assets/js/owl.carousel.min.js"></script>
<script src="assets/js/booking.js"></script>

</body>
</html>