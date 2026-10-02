<?php
// Legacy URL: keyword search now lives on car-listing.php (GET ?q=...).
require_once __DIR__ . '/includes/bootstrap.php';
$q = trim($_REQUEST['searchdata'] ?? $_REQUEST['q'] ?? '');
redirect('car-listing.php' . ($q !== '' ? '?q=' . urlencode($q) : ''));
