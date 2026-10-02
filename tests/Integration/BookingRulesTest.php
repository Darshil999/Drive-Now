<?php

use PHPUnit\Framework\Attributes\DataProvider;

final class BookingRulesTest extends DatabaseTestCase
{
    // Seed data has vehicles 1-8 and users test@gmail.com / amikt12@gmail.com.
    private const CAR = 3;
    private const OTHER_CAR = 4;

    public function testCreatesAPendingBookingWithAUniqueNumber(): void
    {
        $number = create_booking($this->dbh, self::CAR, 'test@gmail.com', '2030-01-10', '2030-01-12', 'Airport pickup');

        $this->assertGreaterThanOrEqual(100000000, $number);
        $row = $this->dbh->query('SELECT BookingNumber, Status, FromDate, ToDate, message FROM tblbooking')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((string) $number, (string) $row['BookingNumber']);
        $this->assertSame(BOOKING_PENDING, (int) $row['Status']);
        $this->assertSame(['2030-01-10', '2030-01-12', 'Airport pickup'], [$row['FromDate'], $row['ToDate'], $row['message']]);
    }

    /** Existing booking: 2030-01-10 .. 2030-01-12 (inclusive). */
    public static function overlappingRanges(): array
    {
        return [
            'identical dates'          => ['2030-01-10', '2030-01-12'],
            'starts inside'            => ['2030-01-11', '2030-01-15'],
            'ends inside'              => ['2030-01-05', '2030-01-10'],
            'encloses existing'        => ['2030-01-01', '2030-01-31'],
            'inside existing'          => ['2030-01-11', '2030-01-11'],
            'starts on the return day' => ['2030-01-12', '2030-01-14'],
        ];
    }

    #[DataProvider('overlappingRanges')]
    public function testOverlappingBookingIsRejected(string $from, string $to): void
    {
        $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_PENDING);

        $this->assertTrue(has_booking_conflict($this->dbh, self::CAR, $from, $to));
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already booked');
        create_booking($this->dbh, self::CAR, 'amikt12@gmail.com', $from, $to);
    }

    public static function freeRanges(): array
    {
        return [
            'day after return'   => ['2030-01-13', '2030-01-15'],
            'day before pick-up' => ['2030-01-05', '2030-01-09'],
        ];
    }

    #[DataProvider('freeRanges')]
    public function testAdjacentDatesAreAllowed(string $from, string $to): void
    {
        $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_CONFIRMED);

        $this->assertFalse(has_booking_conflict($this->dbh, self::CAR, $from, $to));
        $this->assertIsInt(create_booking($this->dbh, self::CAR, 'amikt12@gmail.com', $from, $to));
    }

    public function testOtherCarsDoNotConflict(): void
    {
        $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_CONFIRMED);

        $this->assertIsInt(create_booking($this->dbh, self::OTHER_CAR, 'amikt12@gmail.com', '2030-01-10', '2030-01-12'));
    }

    public function testCancelledBookingsFreeTheDates(): void
    {
        $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_CANCELLED);

        $this->assertFalse(has_booking_conflict($this->dbh, self::CAR, '2030-01-10', '2030-01-12'));
        $this->assertIsInt(create_booking($this->dbh, self::CAR, 'amikt12@gmail.com', '2030-01-10', '2030-01-12'));
    }

    public function testBookingAnUnknownVehicleFails(): void
    {
        $this->expectException(DomainException::class);
        create_booking($this->dbh, 9999, 'test@gmail.com', '2030-01-10', '2030-01-12');
    }

    public function testCustomerCanCancelOwnFutureBookingOnly(): void
    {
        $mine = $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_PENDING, 'test@gmail.com');
        $theirs = $this->insertBooking(self::OTHER_CAR, '2030-01-10', '2030-01-12', BOOKING_PENDING, 'amikt12@gmail.com');

        $this->assertFalse(cancel_customer_booking($this->dbh, $theirs, 'test@gmail.com', '2030-01-01'), 'Not my booking');
        $this->assertFalse(cancel_customer_booking($this->dbh, $mine, 'test@gmail.com', '2030-01-10'), 'Already started');
        $this->assertTrue(cancel_customer_booking($this->dbh, $mine, 'test@gmail.com', '2030-01-09'));
        $this->assertFalse(cancel_customer_booking($this->dbh, $mine, 'test@gmail.com', '2030-01-09'), 'Already cancelled');

        $this->assertSame(BOOKING_CANCELLED, $this->bookingStatus($mine));
        $this->assertSame(BOOKING_PENDING, $this->bookingStatus($theirs));
    }

    public function testAdminCannotConfirmTwoOverlappingBookings(): void
    {
        $first = $this->insertBooking(self::CAR, '2030-01-10', '2030-01-12', BOOKING_PENDING);
        $clash = $this->insertBooking(self::CAR, '2030-01-11', '2030-01-13', BOOKING_PENDING, 'amikt12@gmail.com');

        admin_confirm_booking($this->dbh, $first);
        $this->assertSame(BOOKING_CONFIRMED, $this->bookingStatus($first));

        try {
            admin_confirm_booking($this->dbh, $clash);
            $this->fail('Overlapping confirmation should be refused');
        } catch (DomainException $e) {
            $this->assertStringContainsString('overlaps another confirmed booking', $e->getMessage());
        }
        $this->assertSame(BOOKING_PENDING, $this->bookingStatus($clash));

        // Once the first is cancelled, the second can be confirmed.
        admin_cancel_booking($this->dbh, $first);
        admin_confirm_booking($this->dbh, $clash);
        $this->assertSame(BOOKING_CONFIRMED, $this->bookingStatus($clash));
    }

    public function testAdminActionsOnUnknownBookingFail(): void
    {
        $this->expectException(DomainException::class);
        admin_confirm_booking($this->dbh, 999999);
    }

    /**
     * While one transaction holds the vehicle row lock, a second booking for the
     * same car must wait (here: time out) instead of slipping past the overlap check.
     */
    public function testConcurrentBookingWaitsForTheVehicleLock(): void
    {
        $other = $this->secondConnection();
        $other->beginTransaction();
        $other->query('SELECT id FROM tblvehicles WHERE id = ' . self::CAR . ' FOR UPDATE')->fetchAll();

        $this->dbh->exec('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            create_booking($this->dbh, self::CAR, 'test@gmail.com', '2030-01-10', '2030-01-12');
            $this->fail('Booking should block while another transaction holds the vehicle lock');
        } catch (PDOException $e) {
            $this->assertStringContainsString('Lock wait timeout', $e->getMessage());
        } finally {
            $this->dbh->exec('SET SESSION innodb_lock_wait_timeout = 50');
        }
        $this->assertFalse($this->dbh->inTransaction(), 'Failed booking must roll back');

        // The lock holder books the same dates and commits; the retry then sees the conflict.
        $other->exec("INSERT INTO tblbooking (BookingNumber, userEmail, VehicleId, FromDate, ToDate, message, Status)
            VALUES (111222333, 'amikt12@gmail.com', " . self::CAR . ", '2030-01-10', '2030-01-12', '', 0)");
        $other->commit();

        $this->expectException(DomainException::class);
        create_booking($this->dbh, self::CAR, 'test@gmail.com', '2030-01-10', '2030-01-12');
    }
}
