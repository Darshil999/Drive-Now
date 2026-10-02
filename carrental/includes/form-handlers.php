<?php
/**
 * Handlers for the forms that appear on every public page (login, sign-up
 * and password-reset modals, newsletter box in the footer).
 * Each handler validates input, sets a flash message and redirects
 * (Post/Redirect/Get) so a refresh never re-submits the form.
 */

// ---- Login ----
if (isset($_POST['login'])) {
    $user = attempt_user_login($dbh, $_POST['email'] ?? '', $_POST['password'] ?? '');

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['login'] = $user->EmailId;
        $_SESSION['fname'] = $user->FullName;
        flash('success', 'Welcome back, ' . $user->FullName . '!');
    } else {
        flash('error', 'Invalid email or password.');
    }
    redirect(current_url());
}

// ---- Sign up ----
if (isset($_POST['signup'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['emailid'] ?? '');
    $mobile = trim($_POST['mobileno'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirmpassword'] ?? '';

    $error = null;
    if ($fullname === '' || mb_strlen($fullname) > 120) {
        $error = 'Please enter your full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!is_valid_mobile($mobile)) {
        $error = 'Mobile number must be exactly 10 digits.';
    } else {
        $error = password_problem($password, $confirm);
    }

    if (!$error) {
        $stmt = $dbh->prepare('SELECT 1 FROM tblusers WHERE EmailId = :email');
        $stmt->execute([':email' => $email]);
        if ($stmt->fetchColumn()) {
            $error = 'An account with this email already exists. Please log in.';
        }
    }

    if ($error) {
        flash('error', $error);
    } else {
        $stmt = $dbh->prepare('INSERT INTO tblusers (FullName, EmailId, ContactNo, Password) VALUES (:fname, :email, :mobile, :password)');
        $stmt->execute([
            ':fname' => $fullname,
            ':email' => $email,
            ':mobile' => $mobile,
            ':password' => hash_password($password),
        ]);
        session_regenerate_id(true);
        $_SESSION['login'] = $email;
        $_SESSION['fname'] = $fullname;
        flash('success', 'Your account was created and you are now logged in.');
    }
    redirect(current_url());
}

// ---- Password reset request: email a single-use, time-limited link ----
if (isset($_POST['requestreset'])) {
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
        redirect(current_url());
    }

    $token = create_password_reset($dbh, $email);
    if ($token !== null && !send_password_reset_email($email, $token)) {
        error_log('DriveNow: could not send password reset email to ' . $email);
    }

    // Same answer whether or not the account exists (no account enumeration).
    $message = 'If an account exists for that email, a password reset link has been sent. It expires in '
        . RESET_TOKEN_TTL_MINUTES . ' minutes.';
    if (MAIL_DRIVER === 'log') {
        $message .= ' (Demo mode: emails are written to carrental/storage/outbox.log.)';
    }
    flash('info', $message);
    redirect(current_url());
}

// ---- Newsletter ----
if (isset($_POST['emailsubscibe'])) {
    $subscriber = trim($_POST['subscriberemail'] ?? '');
    if (!filter_var($subscriber, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
    } else {
        $stmt = $dbh->prepare('SELECT 1 FROM tblsubscribers WHERE SubscriberEmail = :email');
        $stmt->execute([':email' => $subscriber]);
        if ($stmt->fetchColumn()) {
            flash('info', 'You are already subscribed.');
        } else {
            $dbh->prepare('INSERT INTO tblsubscribers (SubscriberEmail) VALUES (:email)')
                ->execute([':email' => $subscriber]);
            flash('success', 'Subscribed successfully. Thanks for joining!');
        }
    }
    redirect(current_url());
}
