<?php
// AJAX endpoint used by the sign-up form to check whether an email is free.
require_once __DIR__ . '/includes/config.php';
header('Content-Type: application/json');

$email = trim($_POST['emailid'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['available' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

$stmt = $dbh->prepare('SELECT 1 FROM tblusers WHERE EmailId = :email');
$stmt->execute([':email' => $email]);
echo json_encode($stmt->fetchColumn()
    ? ['available' => false, 'message' => 'Email already registered. Please log in instead.']
    : ['available' => true, 'message' => 'Email available for registration.']);
