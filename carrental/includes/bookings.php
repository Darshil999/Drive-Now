<?php
/**
 * Booking rules shared by the customer pages and the admin panel.
 * Business-rule failures are reported with DomainException (user-safe message).
 */

/** Validates a booking request. Returns an error message or null. */
function booking_validation_error($fromdate, $todate, $message, $today)
{
    if (!is_valid_date($fromdate) || !is_valid_date($todate)) {
        return 'Please choose valid pick-up and return dates.';
    }
    if ($fromdate < $today) {
        return 'The pick-up date cannot be in the past.';
    }
    if ($todate < $fromdate) {
        return 'The return date must be on or after the pick-up date.';
    }
    if (rental_days($fromdate, $todate) > MAX_RENTAL_DAYS) {
        return 'Bookings are limited to ' . MAX_RENTAL_DAYS . ' days. Please contact us for longer rentals.';
    }
    if (mb_strlen((string) $message) > 255) {
        return 'Your message must be 255 characters or fewer.';
    }
    return null;
}

/**
 * True when another booking for the vehicle overlaps [from, to] (inclusive).
 * Two ranges overlap when each one starts on or before the other ends.
 * Cancelled bookings never block; $confirmedOnly limits the check to confirmed ones.
 */
function has_booking_conflict(PDO $dbh, $vehicleId, $fromdate, $todate, $excludeBookingId = null, $confirmedOnly = false)
{
    $sql = 'SELECT COUNT(*) FROM tblbooking
        WHERE VehicleId = :vid AND FromDate <= :todate AND ToDate >= :fromdate AND id <> :exclude';
    $params = [':vid' => (int) $vehicleId, ':todate' => $todate, ':fromdate' => $fromdate, ':exclude' => (int) $excludeBookingId];
    if ($confirmedOnly) {
        $sql .= ' AND Status = ' . BOOKING_CONFIRMED;
    } else {
        $sql .= ' AND Status <> ' . BOOKING_CANCELLED;
    }
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Creates a pending booking and returns its booking number.
 * The vehicle row is locked for the duration of the transaction, so two
 * simultaneous requests for the same car are processed one after the other.
 */
function create_booking(PDO $dbh, $vehicleId, $userEmail, $fromdate, $todate, $message = '')
{
    $dbh->beginTransaction();
    try {
        $lock = $dbh->prepare('SELECT id FROM tblvehicles WHERE id = :vid FOR UPDATE');
        $lock->execute([':vid' => (int) $vehicleId]);
        if (!$lock->fetchColumn()) {
            throw new DomainException('Sorry, that vehicle could not be found.');
        }
        if (has_booking_conflict($dbh, $vehicleId, $fromdate, $todate)) {
            throw new DomainException('This car is already booked for some of those dates. Please pick different dates.');
        }

        $numberTaken = $dbh->prepare('SELECT 1 FROM tblbooking WHERE BookingNumber = :n');
        do {
            $bookingNumber = random_int(100000000, 999999999);
            $numberTaken->execute([':n' => $bookingNumber]);
        } while ($numberTaken->fetchColumn());

        $dbh->prepare('INSERT INTO tblbooking (BookingNumber, userEmail, VehicleId, FromDate, ToDate, message, Status)
            VALUES (:number, :email, :vid, :fromdate, :todate, :message, :status)')
            ->execute([
                ':number' => $bookingNumber,
                ':email' => $userEmail,
                ':vid' => (int) $vehicleId,
                ':fromdate' => $fromdate,
                ':todate' => $todate,
                ':message' => (string) $message,
                ':status' => BOOKING_PENDING,
            ]);
        $dbh->commit();
        return $bookingNumber;
    } catch (Throwable $e) {
        if ($dbh->inTransaction()) {
            $dbh->rollBack();
        }
        throw $e;
    }
}

/** A customer may cancel their own non-cancelled booking before the pick-up date. */
function cancel_customer_booking(PDO $dbh, $bookingId, $userEmail, $today)
{
    $stmt = $dbh->prepare('UPDATE tblbooking SET Status = :cancelled
        WHERE id = :id AND userEmail = :email AND Status <> :cancelled2 AND FromDate > :today');
    $stmt->execute([
        ':cancelled' => BOOKING_CANCELLED,
        ':cancelled2' => BOOKING_CANCELLED,
        ':id' => (int) $bookingId,
        ':email' => $userEmail,
        ':today' => $today,
    ]);
    return $stmt->rowCount() === 1;
}

function find_booking(PDO $dbh, $bookingId)
{
    $stmt = $dbh->prepare('SELECT id, BookingNumber, VehicleId, FromDate, ToDate, Status FROM tblbooking WHERE id = :id');
    $stmt->execute([':id' => (int) $bookingId]);
    return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
}

/** Admin: confirm a booking unless it overlaps another confirmed booking. Returns the booking number. */
function admin_confirm_booking(PDO $dbh, $bookingId)
{
    $booking = find_booking($dbh, $bookingId);
    if (!$booking) {
        throw new DomainException('Booking not found.');
    }
    if (has_booking_conflict($dbh, $booking->VehicleId, $booking->FromDate, $booking->ToDate, $booking->id, true)) {
        throw new DomainException('Cannot confirm booking #' . $booking->BookingNumber . ': it overlaps another confirmed booking for the same car.');
    }
    $dbh->prepare('UPDATE tblbooking SET Status = :status WHERE id = :id')
        ->execute([':status' => BOOKING_CONFIRMED, ':id' => $booking->id]);
    return $booking->BookingNumber;
}

/** Admin: cancel a booking. Returns the booking number. */
function admin_cancel_booking(PDO $dbh, $bookingId)
{
    $booking = find_booking($dbh, $bookingId);
    if (!$booking) {
        throw new DomainException('Booking not found.');
    }
    $dbh->prepare('UPDATE tblbooking SET Status = :status WHERE id = :id')
        ->execute([':status' => BOOKING_CANCELLED, ':id' => $booking->id]);
    return $booking->BookingNumber;
}
