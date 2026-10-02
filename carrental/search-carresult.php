<?php
// Legacy URL: brand/fuel filtering now lives on car-listing.php (GET filters).
require_once __DIR__ . '/includes/bootstrap.php';
redirect('car-listing.php?' . http_build_query([
    'brand' => (int) ($_REQUEST['brand'] ?? 0) ?: '',
    'fueltype' => $_REQUEST['fueltype'] ?? '',
]));
