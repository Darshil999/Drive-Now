<?php
$contact = $dbh->query('SELECT EmailId, ContactNo FROM tblcontactusinfo LIMIT 1')->fetch(PDO::FETCH_OBJ);
$supportEmail = $contact->EmailId ?? '';
$supportPhone = $contact->ContactNo ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header>
  <div class="default-header">
    <div class="container">
      <div class="row">
        <div class="col-sm-3 col-md-2">
          <div class="logo"> <a href="index.php"><img src="assets/images/drivenow-logo.svg" alt="<?php echo e(APP_NAME); ?> home" width="190" height="48"/></a> </div>
        </div>
        <div class="col-sm-9 col-md-10">
          <div class="header_info">
            <div class="header_widgets">
              <div class="circle_icon"> <i class="fa fa-envelope" aria-hidden="true"></i> </div>
              <p class="uppercase_text">For Support Mail us : </p>
              <a href="mailto:<?php echo e($supportEmail); ?>"><?php echo e($supportEmail); ?></a> </div>
            <div class="header_widgets">
              <div class="circle_icon"> <i class="fa fa-phone" aria-hidden="true"></i> </div>
              <p class="uppercase_text">Service Helpline Call Us: </p>
              <a href="tel:<?php echo e($supportPhone); ?>"><?php echo e($supportPhone); ?></a> </div>
            <?php if (!is_logged_in()) { ?>
              <div class="login_btn"> <a href="#loginform" class="btn btn-xs uppercase" data-toggle="modal" data-dismiss="modal">Login / Register</a> </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Navigation -->
  <nav id="navigation_bar" class="navbar navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button id="menu_slide" data-target="#navigation" aria-expanded="false" data-toggle="collapse" class="navbar-toggle collapsed" type="button"> <span class="sr-only">Toggle navigation</span> <span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span> </button>
      </div>
      <div class="header_wrap">
        <?php if (is_logged_in()) { ?>
        <div class="user_login">
          <ul>
            <li class="dropdown"> <a href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-user-circle" aria-hidden="true"></i>
              <?php echo e($_SESSION['fname'] ?? 'My Account'); ?>
              <i class="fa fa-angle-down" aria-hidden="true"></i></a>
              <ul class="dropdown-menu">
                <li><a href="profile.php">Profile Settings</a></li>
                <li><a href="update-password.php">Update Password</a></li>
                <li><a href="my-booking.php">My Bookings</a></li>
                <li><a href="post-testimonial.php">Post a Testimonial</a></li>
                <li><a href="my-testimonials.php">My Testimonials</a></li>
                <li><a href="logout.php">Sign Out</a></li>
              </ul>
            </li>
          </ul>
        </div>
        <?php } ?>
        <div class="header_search">
          <div id="search_toggle"><i class="fa fa-search" aria-hidden="true"></i></div>
          <form action="car-listing.php" method="get" id="header-search-form" role="search">
            <input type="text" placeholder="Search brand, model, fuel..." name="q" class="form-control" aria-label="Search cars" required>
            <button type="submit" aria-label="Search"><i class="fa fa-search" aria-hidden="true"></i></button>
          </form>
        </div>
      </div>
      <div class="collapse navbar-collapse" id="navigation">
        <ul class="nav navbar-nav">
          <li class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>"><a href="index.php">Home</a></li>
          <li><a href="page.php?type=aboutus">About Us</a></li>
          <li class="<?php echo $currentPage === 'car-listing.php' ? 'active' : ''; ?>"><a href="car-listing.php">Car Listing</a></li>
          <li><a href="page.php?type=faqs">FAQs</a></li>
          <li class="<?php echo $currentPage === 'contact-us.php' ? 'active' : ''; ?>"><a href="contact-us.php">Contact Us</a></li>
          <?php if (is_logged_in()) { ?>
            <li class="<?php echo $currentPage === 'my-booking.php' ? 'active' : ''; ?>"><a href="my-booking.php">My Bookings</a></li>
          <?php } ?>
        </ul>
      </div>
    </div>
  </nav>
  <!-- Navigation end -->
</header>
<?php render_flashes('class="flash-container container"'); ?>
