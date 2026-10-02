<?php
/**
 * Application constants and side-effect-free helpers (escaping, flash
 * messages, CSRF, passwords, formatting, uploads).
 * Safe to include from tests: it does not touch the database or start a session.
 */

define('APP_NAME', 'DriveNow');
define('APP_CURRENCY', getenv('APP_CURRENCY') ?: '₹');
define('APP_DEBUG', getenv('APP_DEBUG') === '1');
// Public base URL used in emails (e.g. http://localhost/carrental). Falls back to the request host.
define('APP_URL', rtrim((string) getenv('APP_URL'), '/'));

define('BOOKING_PENDING', 0);
define('BOOKING_CONFIRMED', 1);
define('BOOKING_CANCELLED', 2);
define('MAX_RENTAL_DAYS', 30);
define('MIN_PASSWORD_LENGTH', 8);
define('RESET_TOKEN_TTL_MINUTES', 30);

define('VEHICLE_IMAGE_DIR', __DIR__ . '/../admin/img/vehicleimages/');
// 'log' writes outgoing mail to MAIL_OUTBOX (local/demo); 'mail' uses PHP mail().
define('MAIL_DRIVER', getenv('MAIL_DRIVER') ?: 'log');
define('MAIL_OUTBOX', getenv('MAIL_OUTBOX') ?: __DIR__ . '/../storage/outbox.log');

/* ---------- Output & navigation ---------- */

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/** Current request path + query, safe to use as a same-site redirect target. */
function current_url()
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return (strpos($uri, '/') === 0 && strpos($uri, '//') !== 0) ? $uri : '/';
}

/** Absolute URL to a page in the public site (used in emails). */
function app_url($path)
{
    $base = APP_URL;
    if ($base === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
    }
    return $base . '/' . ltrim($path, '/');
}

function flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes()
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Prints pending flash messages inside an optional wrapper element (nothing when empty). */
function render_flashes($wrapperAttributes = '')
{
    $messages = take_flashes();
    if (!$messages) {
        return;
    }
    if ($wrapperAttributes !== '') {
        echo '<div ' . $wrapperAttributes . '>';
    }
    foreach ($messages as $f) {
        $class = $f['type'] === 'error' ? 'danger' : $f['type'];
        echo '<div class="alert alert-' . e($class) . ' alert-dismissible flash-message" role="alert">'
            . '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
            . e($f['message']) . '</div>';
    }
    if ($wrapperAttributes !== '') {
        echo '</div>';
    }
}

/* ---------- CSRF ---------- */

function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid($token)
{
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

/* ---------- Passwords ---------- */

function is_logged_in()
{
    return !empty($_SESSION['login']);
}

function hash_password($plain)
{
    return password_hash($plain, PASSWORD_DEFAULT);
}

function is_legacy_md5_hash($stored)
{
    return is_string($stored) && (bool) preg_match('/^[a-f0-9]{32}$/i', $stored);
}

/**
 * Verifies a password against either a modern hash or a legacy MD5 hash
 * (accounts created before the password_hash() migration).
 */
function password_matches($plain, $stored)
{
    if (!is_string($stored) || $stored === '') {
        return false;
    }
    if (is_legacy_md5_hash($stored)) {
        return hash_equals(strtolower($stored), md5((string) $plain));
    }
    return password_verify((string) $plain, $stored);
}

function password_problem($password, $confirm)
{
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        return 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.';
    }
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return 'Password must contain at least one letter and one number.';
    }
    if ($password !== $confirm) {
        return 'Password and confirm password do not match.';
    }
    return null;
}

/* ---------- Formatting & validation ---------- */

function format_price($amount)
{
    return APP_CURRENCY . number_format((float) $amount);
}

function is_valid_date($value)
{
    $d = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $d && $d->format('Y-m-d') === $value;
}

/** Number of rental days, counting both the pick-up and the return day. */
function rental_days($from, $to)
{
    $diff = (new DateTime($from))->diff(new DateTime($to));
    return $diff->invert ? 0 : $diff->days + 1;
}

function format_date($value)
{
    return $value ? date('d M Y', strtotime($value)) : '';
}

function booking_status_label($status)
{
    switch ((int) $status) {
        case BOOKING_CONFIRMED: return ['Confirmed', 'success'];
        case BOOKING_CANCELLED: return ['Cancelled', 'danger'];
        default:                return ['Pending', 'warning'];
    }
}

function is_valid_mobile($value)
{
    return (bool) preg_match('/^[0-9]{10}$/', (string) $value);
}

/* ---------- Mail ---------- */

/**
 * Sends an email. With MAIL_DRIVER=log (the default, for local/demo use) the
 * message is appended to MAIL_OUTBOX instead of being delivered.
 */
function send_mail($to, $subject, $body)
{
    if (MAIL_DRIVER === 'mail') {
        return mail($to, $subject, $body, 'From: ' . APP_NAME . ' <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '>');
    }
    $entry = '==== ' . date('Y-m-d H:i:s') . " ====\nTo: $to\nSubject: $subject\n\n$body\n\n";
    $dir = dirname(MAIL_OUTBOX);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    return file_put_contents(MAIL_OUTBOX, $entry, FILE_APPEND | LOCK_EX) !== false;
}

/* ---------- Uploads ---------- */

/**
 * Validates and stores an uploaded vehicle image under a random name.
 * Returns the stored filename, null when no file was sent, or throws
 * RuntimeException with a user-friendly message.
 */
function store_vehicle_image($field, $required = true)
{
    $file = $_FILES[$field] ?? null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('Please choose an image for ' . $field . '.');
        }
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed for ' . $field . ' (error code ' . (int) $file['error'] . ').');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Images must be 5 MB or smaller.');
    }

    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset($allowed[$info[2]])) {
        throw new RuntimeException('Only JPG, PNG, GIF or WEBP images are allowed.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], VEHICLE_IMAGE_DIR . $name)) {
        throw new RuntimeException('Could not save the uploaded image. Check folder permissions.');
    }
    return $name;
}
