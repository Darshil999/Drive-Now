<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordTest extends TestCase
{
    public function testBcryptHashMatchesOnlyTheRightPassword(): void
    {
        $hash = hash_password('Secret123');

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_matches('Secret123', $hash));
        $this->assertFalse(password_matches('secret123', $hash));
    }

    public function testLegacyMd5HashIsStillAccepted(): void
    {
        $legacy = md5('Test@123');

        $this->assertTrue(is_legacy_md5_hash($legacy));
        $this->assertTrue(password_matches('Test@123', $legacy));
        $this->assertTrue(password_matches('Test@123', strtoupper($legacy)));
        $this->assertFalse(password_matches('Wrong@123', $legacy));
    }

    public function testBcryptHashIsNotTreatedAsLegacy(): void
    {
        $this->assertFalse(is_legacy_md5_hash(hash_password('Secret123')));
    }

    public function testEmptyOrMissingStoredHashNeverMatches(): void
    {
        $this->assertFalse(password_matches('', ''));
        $this->assertFalse(password_matches('anything', null));
        $this->assertFalse(password_matches('anything', ''));
    }

    public static function weakPasswords(): array
    {
        return [
            'too short'      => ['Ab1', 'Ab1', 'at least'],
            'no digit'       => ['Password', 'Password', 'letter and one number'],
            'no letter'      => ['12345678', '12345678', 'letter and one number'],
            'confirm differs' => ['Secret123', 'Secret124', 'do not match'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function testPasswordRulesRejectWeakOrMismatchedPasswords(string $password, string $confirm, string $expected): void
    {
        $this->assertStringContainsString($expected, (string) password_problem($password, $confirm));
    }

    public function testPasswordRulesAcceptAStrongMatchingPassword(): void
    {
        $this->assertNull(password_problem('Secret123', 'Secret123'));
    }
}
