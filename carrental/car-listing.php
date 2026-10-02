<?php
include('includes/config.php');

// Optional filters (GET so results can be bookmarked/shared).
$brandFilter = (int) ($_GET['brand'] ?? 0);
$fuelFilter = trim($_GET['fueltype'] ?? '');
$keyword = trim($_GET['q'] ?? '');
$sortOptions = [
    'newest' => 'tblvehicles.id DESC',
    'price_asc' => 'tblvehicles.PricePerDay ASC',
    'price_desc' => 'tblvehicles.PricePerDay DESC',
    'year_desc' => 'tblvehicles.ModelYear DESC',
];
$sort = isset($sortOptions[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'newest';

$where = [];
$params = [];
if ($brandFilter > 0) {
    $where[] = 'tblvehicles.VehiclesBrand = :brand';
    $params[':brand'] = $brandFilter;
}
if ($fuelFilter !== '') {
    $where[] = 'tblvehicles.FuelType = :fueltype';
    $params[':fueltype'] = $fuelFilter;
}
if ($keyword !== '') {
    // Partial, case-insensitive match on model, brand, fuel type or year.
    $where[] = '(tblvehicles.VehiclesTitle LIKE :q OR tblbrands.BrandName LIKE :q OR tblvehicles.FuelType LIKE :q OR tblvehicles.ModelYear LIKE :q)';
    $params[':q'] = '%' . addcslashes($keyword, '%_\\') . '%';
}

$listingStmt = $dbh->prepare('SELECT tblvehicles.*, tblbrands.BrandName
    FROM tblvehicles JOIN tblbrands ON tblbrands.id = tblvehicles.VehiclesBrand'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY ' . $sortOptions[$sort]);
$listingStmt->execute($params);
$vehicles = $listingStmt->fetchAll(PDO::FETCH_OBJ);

$brands = $dbh->query('SELECT id, BrandName FROM tblbrands ORDER BY BrandName')->fetchAll(PDO::FETCH_OBJ);
$fuelTypes = $dbh->query("SELECT DISTINCT FuelType FROM tblvehicles WHERE FuelType <> '' ORDER BY FuelType")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE HTML>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>DriveNow | Car Listing</title>
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
<link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
</head>
<body>


<!--Header--> 
<?php include('includes/header.php');?>
<!-- /Header --> 

<!--Page Header-->
<section class="page-header listing_page">
  <div class="container">
    <div class="page-header_wrap">
      <div class="page-heading">
        <h1><?php echo $keyword !== "" ? "Search Results" : "Car Listing"; ?></h1>
      </div>
      <ul class="coustom-breadcrumb">
        <li><a href="index.php">Home</a></li>
        <li>Car Listing</li>
      </ul>
    </div>
  </div>
  <!-- Dark Overlay-->
  <div class="dark-overlay"></div>
</section>
<!-- /Page Header--> 

<!--Listing-->
<section class="listing-page">
  <div class="container">
    <div class="row">
      <div class="col-md-9 col-md-push-3">
        <div class="result-sorting-wrapper">
          <div class="sorting-count">
            <p class="listing-filter-summary"><span><?php echo count($vehicles); ?> <?php echo count($vehicles) === 1 ? 'car' : 'cars'; ?> found</span><?php if ($keyword !== "") { ?> for &ldquo;<?php echo e($keyword); ?>&rdquo;<?php } ?>
              <?php if ($brandFilter || $fuelFilter !== '' || $keyword !== '') { ?><a href="car-listing.php">Clear filters</a><?php } ?></p>
          </div>
          <div class="result-sorting-by">
            <form method="get" class="form-inline">
              <input type="hidden" name="brand" value="<?php echo $brandFilter ?: ''; ?>">
              <input type="hidden" name="fueltype" value="<?php echo e($fuelFilter); ?>">
              <input type="hidden" name="q" value="<?php echo e($keyword); ?>">
              <label for="sort">Sort by:</label>
              <select class="form-control" id="sort" name="sort" onchange="this.form.submit()">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: low to high</option>
                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: high to low</option>
                <option value="year_desc" <?php echo $sort === 'year_desc' ? 'selected' : ''; ?>>Model year</option>
              </select>
              <noscript><button type="submit" class="btn btn-xs">Apply</button></noscript>
            </form>
          </div>
        </div>

<?php if (!$vehicles) { ?>
        <div class="empty-state">
          <i class="fa fa-car" aria-hidden="true"></i>
          <p>No cars match your filters. Try a different brand or fuel type.</p>
          <a href="car-listing.php" class="btn">Show all cars</a>
        </div>
<?php } ?>
<?php foreach ($vehicles as $result) { ?>
        <div class="product-listing-m gray-bg">
          <div class="product-listing-img"><a href="vehical-details.php?vhid=<?php echo (int) $result->id;?>"><img src="admin/img/vehicleimages/<?php echo e($result->Vimage1);?>" class="img-responsive" alt="<?php echo e($result->BrandName . ' ' . $result->VehiclesTitle);?>" /></a>
          </div>
          <div class="product-listing-content">
            <h5><a href="vehical-details.php?vhid=<?php echo (int) $result->id;?>"><?php echo e($result->BrandName);?>, <?php echo e($result->VehiclesTitle);?></a></h5>
            <p class="list-price"><?php echo format_price($result->PricePerDay);?> per day</p>
            <ul>
              <li><i class="fa fa-user" aria-hidden="true"></i><?php echo e($result->SeatingCapacity);?> seats</li>
              <li><i class="fa fa-calendar" aria-hidden="true"></i><?php echo e($result->ModelYear);?> model</li>
              <li><i class="fa fa-car" aria-hidden="true"></i><?php echo e($result->FuelType);?></li>
            </ul>
            <a href="vehical-details.php?vhid=<?php echo (int) $result->id;?>" class="btn">View Details <span class="angle_arrow"><i class="fa fa-angle-right" aria-hidden="true"></i></span></a>
          </div>
        </div>
<?php } ?>
      </div>

      <!--Side-Bar-->
      <aside class="col-md-3 col-md-pull-9">
        <div class="sidebar_widget">
          <div class="widget_heading">
            <h5><i class="fa fa-filter" aria-hidden="true"></i> Find Your Car</h5>
          </div>
          <div class="sidebar_filter">
            <form action="car-listing.php" method="get">
              <div class="form-group select">
                <select class="form-control" name="brand" aria-label="Brand">
                  <option value="">All brands</option>
                  <?php foreach ($brands as $brand) { ?>
                    <option value="<?php echo (int) $brand->id; ?>" <?php echo $brandFilter === (int) $brand->id ? 'selected' : ''; ?>><?php echo e($brand->BrandName); ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="form-group select">
                <select class="form-control" name="fueltype" aria-label="Fuel type">
                  <option value="">All fuel types</option>
                  <?php foreach ($fuelTypes as $fuel) { ?>
                    <option value="<?php echo e($fuel); ?>" <?php echo $fuelFilter === $fuel ? 'selected' : ''; ?>><?php echo e($fuel); ?></option>
                  <?php } ?>
                </select>
              </div>
              <input type="hidden" name="sort" value="<?php echo e($sort); ?>">
              <input type="hidden" name="q" value="<?php echo e($keyword); ?>">
              <div class="form-group">
                <button type="submit" class="btn btn-block"><i class="fa fa-search" aria-hidden="true"></i> Search Car</button>
              </div>
            </form>
          </div>
        </div>

        <div class="sidebar_widget">
          <div class="widget_heading">
            <h5><i class="fa fa-car" aria-hidden="true"></i> Recently Listed Cars</h5>
          </div>
          <div class="recent_addedcars">
            <ul>
<?php $sql = "SELECT tblvehicles.*,tblbrands.BrandName,tblbrands.id as bid  from tblvehicles join tblbrands on tblbrands.id=tblvehicles.VehiclesBrand order by id desc limit 4";
$query = $dbh -> prepare($sql);
$query->execute();
$results=$query->fetchAll(PDO::FETCH_OBJ);
$cnt=1;
if($query->rowCount() > 0)
{
foreach($results as $result)
{  ?>

              <li class="gray-bg">
                <div class="recent_post_img"> <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id);?>"><img src="admin/img/vehicleimages/<?php echo htmlentities($result->Vimage1);?>" alt="image"></a> </div>
                <div class="recent_post_title"> <a href="vehical-details.php?vhid=<?php echo htmlentities($result->id);?>"><?php echo htmlentities($result->BrandName);?> , <?php echo htmlentities($result->VehiclesTitle);?></a>
                  <p class="widget_price"><?php echo format_price($result->PricePerDay);?> Per Day</p>
                </div>
              </li>
              <?php }} ?>
              
            </ul>
          </div>
        </div>
      </aside>
      <!--/Side-Bar--> 
    </div>
  </div>
</section>
<!-- /Listing--> 

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
