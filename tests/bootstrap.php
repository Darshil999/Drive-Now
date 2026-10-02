<?php
// PHPUnit bootstrap: loads the application's side-effect-free code (no DB connection, no session).
require __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../carrental/includes/functions.php';
require __DIR__ . '/../carrental/includes/auth.php';
require __DIR__ . '/../carrental/includes/bookings.php';

require __DIR__ . '/DatabaseTestCase.php';

$_SESSION = [];
