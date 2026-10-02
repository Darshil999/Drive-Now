<?php
/**
 * Authentication and password-reset logic (no output, no session handling).
 */

/** Re-hashes a legacy MD5 / outdated hash after a successful login. */
function upgrade_password_hash(PDO $dbh, $table, $keyColumn, $keyValue, $plain, $stored)
{
    $allowed = ['tblusers' => 'EmailId', 'admin' => 'UserName'];
    if (($allowed[$table] ?? null) !== $keyColumn) {
        throw new InvalidArgumentException('Unsupported table/column for password upgrade.');
    }
    if (is_legacy_md5_hash($stored) || password_needs_rehash($stored, PASSWORD_DEFAULT)) {
        $dbh->prepare("UPDATE $table SET Password = :hash WHERE $keyColumn = :key")
            ->execute([':hash' => hash_password($plain), ':key' => $keyValue]);
    }
}

/** Returns the customer row (EmailId, FullName) on success, or null. */
function attempt_user_login(PDO $dbh, $email, $password)
{
    $stmt = $dbh->prepare('SELECT EmailId, FullName, Password FROM tblusers WHERE EmailId = :email LIMIT 1');
    $stmt->execute([':email' => trim((string) $email)]);
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$user || !password_matches($password, $user->Password)) {
        return null;
    }
    upgrade_password_hash($dbh, 'tblusers', 'EmailId', $user->EmailId, $password, $user->Password);
    unset($user->Password);
    return $user;
}

/** Returns the admin username on success, or null. */
function attempt_admin_login(PDO $dbh, $username, $password)
{
    $stmt = $dbh->prepare('SELECT UserName, Password FROM admin WHERE UserName = :username LIMIT 1');
    $stmt->execute([':username' => trim((string) $username)]);
    $admin = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$admin || !password_matches($password, $admin->Password)) {
        return null;
    }
    upgrade_password_hash($dbh, 'admin', 'UserName', $admin->UserName, $password, $admin->Password);
    return $admin->UserName;
}

/* ---------- Password reset ----------
 * The raw token only ever exists in the emailed link; the database stores its
 * SHA-256 hash, so a leaked database cannot be used to reset passwords.
 * Tokens expire after RESET_TOKEN_TTL_MINUTES and can be used once.
 */

/**
 * Creates a reset token for a registered email and returns the raw token.
 * Returns null for unknown emails, or when a token was issued in the last
 * minute (simple throttle). Callers must show the same message either way.
 */
function create_password_reset(PDO $dbh, $email)
{
    $email = trim((string) $email);
    $user = $dbh->prepare('SELECT 1 FROM tblusers WHERE EmailId = :email');
    $user->execute([':email' => $email]);
    if (!$user->fetchColumn()) {
        return null;
    }

    $recent = $dbh->prepare('SELECT 1 FROM tblpasswordresets
        WHERE EmailId = :email AND CreatedAt > NOW() - INTERVAL 1 MINUTE');
    $recent->execute([':email' => $email]);
    if ($recent->fetchColumn()) {
        return null;
    }

    // Only the newest link is valid.
    $dbh->prepare('UPDATE tblpasswordresets SET UsedAt = NOW() WHERE EmailId = :email AND UsedAt IS NULL')
        ->execute([':email' => $email]);

    $token = bin2hex(random_bytes(32));
    $dbh->prepare('INSERT INTO tblpasswordresets (EmailId, TokenHash, ExpiresAt)
        VALUES (:email, :hash, NOW() + INTERVAL ' . (int) RESET_TOKEN_TTL_MINUTES . ' MINUTE)')
        ->execute([':email' => $email, ':hash' => hash('sha256', $token)]);
    return $token;
}

/** Returns the email a valid (unused, unexpired) token belongs to, or null. */
function find_password_reset_email(PDO $dbh, $token)
{
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $stmt = $dbh->prepare('SELECT EmailId FROM tblpasswordresets
        WHERE TokenHash = :hash AND UsedAt IS NULL AND ExpiresAt > NOW()');
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $email = $stmt->fetchColumn();
    return $email === false ? null : $email;
}

/** Sets the new password and burns the token. Returns false if the token is invalid. */
function complete_password_reset(PDO $dbh, $token, $newPassword)
{
    $email = find_password_reset_email($dbh, $token);
    if ($email === null) {
        return false;
    }
    $dbh->beginTransaction();
    try {
        // Mark used first; if another request already used it, rowCount() is 0.
        $burn = $dbh->prepare('UPDATE tblpasswordresets SET UsedAt = NOW()
            WHERE TokenHash = :hash AND UsedAt IS NULL AND ExpiresAt > NOW()');
        $burn->execute([':hash' => hash('sha256', $token)]);
        if ($burn->rowCount() !== 1) {
            $dbh->rollBack();
            return false;
        }
        $dbh->prepare('UPDATE tblusers SET Password = :password WHERE EmailId = :email')
            ->execute([':password' => hash_password($newPassword), ':email' => $email]);
        $dbh->prepare('UPDATE tblpasswordresets SET UsedAt = NOW() WHERE EmailId = :email AND UsedAt IS NULL')
            ->execute([':email' => $email]);
        $dbh->commit();
        return true;
    } catch (Throwable $e) {
        $dbh->rollBack();
        throw $e;
    }
}

function send_password_reset_email($email, $token)
{
    $link = app_url('reset-password.php?token=' . $token);
    $body = "Hi,\n\nWe received a request to reset your " . APP_NAME . " password.\n"
        . "Open this link within " . RESET_TOKEN_TTL_MINUTES . " minutes to choose a new one:\n\n$link\n\n"
        . "If you did not request this, you can ignore this email.\n";
    return send_mail($email, APP_NAME . ' password reset', $body);
}
