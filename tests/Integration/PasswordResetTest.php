<?php

final class PasswordResetTest extends DatabaseTestCase
{
    private const EMAIL = 'reset@example.com';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser(self::EMAIL, 'OldPass123');
    }

    /** Pretend the last token was issued long enough ago to pass the 1-minute throttle. */
    private function ageTokens(): void
    {
        $this->dbh->exec('UPDATE tblpasswordresets SET CreatedAt = NOW() - INTERVAL 5 MINUTE');
    }

    public function testResetIssuesATokenAndStoresOnlyItsHash(): void
    {
        $token = create_password_reset($this->dbh, self::EMAIL);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $row = $this->dbh->query('SELECT TokenHash, ExpiresAt > NOW() AS valid FROM tblpasswordresets')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(hash('sha256', $token), $row['TokenHash']);
        $this->assertNotSame($token, $row['TokenHash']);
        $this->assertSame(1, (int) $row['valid']);
    }

    public function testUnknownEmailGetsNoToken(): void
    {
        $this->assertNull(create_password_reset($this->dbh, 'nobody@example.com'));
        $this->assertSame(0, (int) $this->dbh->query('SELECT COUNT(*) FROM tblpasswordresets')->fetchColumn());
    }

    public function testRepeatRequestsWithinAMinuteAreThrottled(): void
    {
        $this->assertNotNull(create_password_reset($this->dbh, self::EMAIL));
        $this->assertNull(create_password_reset($this->dbh, self::EMAIL));
    }

    public function testValidTokenResetsThePassword(): void
    {
        $token = create_password_reset($this->dbh, self::EMAIL);

        $this->assertSame(self::EMAIL, find_password_reset_email($this->dbh, $token));
        $this->assertTrue(complete_password_reset($this->dbh, $token, 'NewPass456'));

        $this->assertNotNull(attempt_user_login($this->dbh, self::EMAIL, 'NewPass456'));
        $this->assertNull(attempt_user_login($this->dbh, self::EMAIL, 'OldPass123'));
    }

    public function testTokenCanOnlyBeUsedOnce(): void
    {
        $token = create_password_reset($this->dbh, self::EMAIL);
        $this->assertTrue(complete_password_reset($this->dbh, $token, 'NewPass456'));

        $this->assertNull(find_password_reset_email($this->dbh, $token));
        $this->assertFalse(complete_password_reset($this->dbh, $token, 'Hijack789'));
        $this->assertNotNull(attempt_user_login($this->dbh, self::EMAIL, 'NewPass456'));
    }

    public function testExpiredTokenIsRejected(): void
    {
        $token = create_password_reset($this->dbh, self::EMAIL);
        $this->dbh->exec('UPDATE tblpasswordresets SET ExpiresAt = NOW() - INTERVAL 1 SECOND');

        $this->assertNull(find_password_reset_email($this->dbh, $token));
        $this->assertFalse(complete_password_reset($this->dbh, $token, 'NewPass456'));
        $this->assertNotNull(attempt_user_login($this->dbh, self::EMAIL, 'OldPass123'));
    }

    public function testNewRequestInvalidatesTheOlderLink(): void
    {
        $first = create_password_reset($this->dbh, self::EMAIL);
        $this->ageTokens();
        $second = create_password_reset($this->dbh, self::EMAIL);

        $this->assertNull(find_password_reset_email($this->dbh, $first));
        $this->assertSame(self::EMAIL, find_password_reset_email($this->dbh, $second));
    }

    public function testMalformedOrUnknownTokensAreRejected(): void
    {
        create_password_reset($this->dbh, self::EMAIL);

        $this->assertNull(find_password_reset_email($this->dbh, ''));
        $this->assertNull(find_password_reset_email($this->dbh, "' OR 1=1 --"));
        $this->assertNull(find_password_reset_email($this->dbh, str_repeat('a', 64)));
        $this->assertNull(find_password_reset_email($this->dbh, ['array']));
    }
}
