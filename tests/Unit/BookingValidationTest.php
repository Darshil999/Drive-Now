<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BookingValidationTest extends TestCase
{
    private const TODAY = '2030-06-15';

    public static function invalidRequests(): array
    {
        return [
            'not a date'           => ['tomorrow', '2030-06-20', '', 'valid pick-up and return dates'],
            'impossible date'      => ['2030-02-30', '2030-03-02', '', 'valid pick-up and return dates'],
            'wrong format'         => ['15/06/2030', '2030-06-20', '', 'valid pick-up and return dates'],
            'pick-up in the past'  => ['2030-06-14', '2030-06-20', '', 'cannot be in the past'],
            'return before pickup' => ['2030-06-20', '2030-06-19', '', 'on or after the pick-up date'],
            'longer than 30 days'  => ['2030-06-15', '2030-07-15', '', 'limited to 30 days'],
            'message too long'     => ['2030-06-15', '2030-06-16', str_repeat('x', 256), '255 characters'],
        ];
    }

    #[DataProvider('invalidRequests')]
    public function testInvalidRequestsAreRejected(string $from, string $to, string $message, string $expected): void
    {
        $this->assertStringContainsString($expected, (string) booking_validation_error($from, $to, $message, self::TODAY));
    }

    public static function validRequests(): array
    {
        return [
            'same-day rental'       => ['2030-06-15', '2030-06-15'],
            'starting today'        => ['2030-06-15', '2030-06-18'],
            'exactly the 30 day max' => ['2030-06-15', '2030-07-14'],
        ];
    }

    #[DataProvider('validRequests')]
    public function testValidRequestsPass(string $from, string $to): void
    {
        $this->assertNull(booking_validation_error($from, $to, 'Airport pickup', self::TODAY));
    }

    public function testRentalDaysCountBothPickupAndReturnDay(): void
    {
        $this->assertSame(1, rental_days('2030-06-15', '2030-06-15'));
        $this->assertSame(3, rental_days('2030-06-15', '2030-06-17'));
        $this->assertSame(2, rental_days('2030-02-28', '2030-03-01'));
        $this->assertSame(0, rental_days('2030-06-17', '2030-06-15'));
    }

    public function testDateValidation(): void
    {
        $this->assertTrue(is_valid_date('2028-02-29'));
        $this->assertFalse(is_valid_date('2030-02-29'));
        $this->assertFalse(is_valid_date('2030-6-1'));
        $this->assertFalse(is_valid_date(''));
    }
}
