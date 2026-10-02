<?php
/**
 * Confirm or cancel a booking (POST from action_button()).
 * Shared by manage-bookings.php and bookig-details.php.
 */
$confirmId = posted_action_id('confirm_booking');
$cancelId = posted_action_id('cancel_booking');

if ($confirmId !== null || $cancelId !== null) {
    try {
        if ($confirmId !== null) {
            flash('success', 'Booking #' . admin_confirm_booking($dbh, $confirmId) . ' confirmed.');
        } else {
            flash('success', 'Booking #' . admin_cancel_booking($dbh, $cancelId) . ' cancelled.');
        }
    } catch (DomainException $ex) {
        flash('error', $ex->getMessage());
    }
    redirect(current_url());
}
