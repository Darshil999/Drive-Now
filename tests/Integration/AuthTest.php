<?php

final class AuthTest extends DatabaseTestCase
{
    private function storedUserHash(string $email): string
    {
        $stmt = $this->dbh->prepare('SELECT Password FROM tblusers WHERE EmailId = :email');
        $stmt->execute([':email' => $email]);
        return (string) $stmt->fetchColumn();
    }

    public function testCustomerCanLogInWithCorrectPassword(): void
    {
        $this->createUser('rider@example.com', 'Secret123');

        $user = attempt_user_login($this->dbh, 'rider@example.com', 'Secret123');

        $this->assertNotNull($user);
        $this->assertSame('rider@example.com', $user->EmailId);
        $this->assertObjectNotHasProperty('Password', $user, 'Password hash must not leave the auth layer');
    }

    public function testLoginFailsForWrongPasswordOrUnknownEmail(): void
    {
        $this->createUser('rider@example.com', 'Secret123');

        $this->assertNull(attempt_user_login($this->dbh, 'rider@example.com', 'Secret124'));
        $this->assertNull(attempt_user_login($this->dbh, 'nobody@example.com', 'Secret123'));
        $this->assertNull(attempt_user_login($this->dbh, '', ''));
    }

    public function testLegacyMd5PasswordIsUpgradedToBcryptOnLogin(): void
    {
        $this->dbh->prepare('UPDATE tblusers SET Password = :md5 WHERE EmailId = :email')
            ->execute([':md5' => md5('Test@123'), ':email' => 'test@gmail.com']);

        $this->assertNotNull(attempt_user_login($this->dbh, 'test@gmail.com', 'Test@123'));

        $upgraded = $this->storedUserHash('test@gmail.com');
        $this->assertStringStartsWith('$2y$', $upgraded);
        $this->assertNotNull(attempt_user_login($this->dbh, 'test@gmail.com', 'Test@123'), 'Still works after the upgrade');
    }

    public function testFailedLoginDoesNotChangeTheStoredHash(): void
    {
        $this->dbh->prepare('UPDATE tblusers SET Password = :md5 WHERE EmailId = :email')
            ->execute([':md5' => md5('Test@123'), ':email' => 'test@gmail.com']);

        attempt_user_login($this->dbh, 'test@gmail.com', 'wrong');

        $this->assertSame(md5('Test@123'), $this->storedUserHash('test@gmail.com'));
    }

    public function testAdminLoginWorksAndUpgradesLegacyHash(): void
    {
        $this->dbh->exec("UPDATE admin SET Password = '" . md5('Test@12345') . "' WHERE UserName = 'admin'");

        $this->assertSame('admin', attempt_admin_login($this->dbh, 'admin', 'Test@12345'));
        $this->assertNull(attempt_admin_login($this->dbh, 'admin', 'Test@123'));

        $hash = $this->dbh->query("SELECT Password FROM admin WHERE UserName = 'admin'")->fetchColumn();
        $this->assertStringStartsWith('$2y$', $hash);
    }

    public function testPasswordUpgradeOnlyAcceptsKnownTables(): void
    {
        $this->expectException(InvalidArgumentException::class);
        upgrade_password_hash($this->dbh, 'tblbrands', 'BrandName', 'x', 'x', md5('x'));
    }
}
