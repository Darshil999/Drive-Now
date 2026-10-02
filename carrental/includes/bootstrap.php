<?php
/**
 * Shared bootstrap for the public site and the admin panel:
 * database connection, hardened session, CSRF check on every POST.
 * Helpers and business rules live in functions.php, auth.php and bookings.php.
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/bookings.php';

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'carrental');

ini_set('display_errors', APP_DEBUG ? '1' : '0');

try {
    $dbh = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log('DriveNow DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit(APP_DEBUG
        ? 'Database connection failed: ' . htmlspecialchars($e->getMessage())
        : 'The service is temporarily unavailable. Please check the database configuration.');
}

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

// Every state-changing request (all are POST) must carry the session's CSRF token.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrf_valid($_POST['csrf_token'] ?? '')) {
    flash('error', 'Your session expired or the form was invalid. Please try again.');
    redirect(current_url());
}
