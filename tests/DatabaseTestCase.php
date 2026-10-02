<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that need MySQL.
 *
 * On first use it (re)creates a dedicated database (TEST_DB_NAME, default
 * `carrental_test`) from SQL File/carrental.sql, so tests never touch the
 * real `carrental` data. Connection settings come from TEST_DB_HOST,
 * TEST_DB_USER and TEST_DB_PASS. If MySQL is unreachable the tests are
 * skipped, unless REQUIRE_DB_TESTS=1 (set in CI), in which case they fail.
 */
abstract class DatabaseTestCase extends TestCase
{
    private static ?PDO $pdo = null;

    protected PDO $dbh;

    protected function setUp(): void
    {
        $this->dbh = self::connection();
        // Each test starts with no bookings and no reset tokens.
        $this->dbh->exec('DELETE FROM tblbooking');
        $this->dbh->exec('DELETE FROM tblpasswordresets');
    }

    protected static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
        $user = getenv('TEST_DB_USER') ?: 'root';
        $pass = getenv('TEST_DB_PASS') !== false ? getenv('TEST_DB_PASS') : '';
        $name = getenv('TEST_DB_NAME') ?: 'carrental_test';

        try {
            $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            $message = 'MySQL not available for integration tests: ' . $e->getMessage();
            if (getenv('REQUIRE_DB_TESTS') === '1') {
                self::fail($message);
            }
            self::markTestSkipped($message);
        }

        $pdo->exec("DROP DATABASE IF EXISTS `$name`");
        $pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$name`");

        // Import the same dump used for real installs; iterate rowsets so any SQL error surfaces.
        $stmt = $pdo->query(file_get_contents(__DIR__ . '/../SQL File/carrental.sql'));
        while ($stmt->nextRowset()) {
        }
        $stmt->closeCursor();

        return self::$pdo = $pdo;
    }

    /** A second, independent connection (for locking/concurrency tests). */
    protected function secondConnection(): PDO
    {
        $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
        $user = getenv('TEST_DB_USER') ?: 'root';
        $pass = getenv('TEST_DB_PASS') !== false ? getenv('TEST_DB_PASS') : '';
        $name = getenv('TEST_DB_NAME') ?: 'carrental_test';
        return new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    protected function createUser(string $email, string $password): void
    {
        $this->dbh->prepare('DELETE FROM tblusers WHERE EmailId = :email')->execute([':email' => $email]);
        $this->dbh->prepare("INSERT INTO tblusers (FullName, EmailId, ContactNo, Password) VALUES ('Test User', :email, '9999999999', :password)")
            ->execute([':email' => $email, ':password' => hash_password($password)]);
    }

    protected function insertBooking(int $vehicleId, string $from, string $to, int $status, string $email = 'test@gmail.com'): int
    {
        $this->dbh->prepare('INSERT INTO tblbooking (BookingNumber, userEmail, VehicleId, FromDate, ToDate, message, Status)
            VALUES (:n, :email, :vid, :from, :to, \'\', :status)')
            ->execute([':n' => random_int(100000000, 999999999), ':email' => $email, ':vid' => $vehicleId, ':from' => $from, ':to' => $to, ':status' => $status]);
        return (int) $this->dbh->lastInsertId();
    }

    protected function bookingStatus(int $id): int
    {
        $stmt = $this->dbh->prepare('SELECT Status FROM tblbooking WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return (int) $stmt->fetchColumn();
    }
}
