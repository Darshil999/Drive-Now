<?php

use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsRandomHexAndStableWithinASession(): void
    {
        $token = csrf_token();

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        $this->assertSame($token, csrf_token());
    }

    public function testOnlyTheSessionTokenIsValid(): void
    {
        $token = csrf_token();

        $this->assertTrue(csrf_valid($token));
        $this->assertFalse(csrf_valid(str_repeat('0', 64)));
        $this->assertFalse(csrf_valid(''));
        $this->assertFalse(csrf_valid(null));
        $this->assertFalse(csrf_valid([$token]));
    }

    public function testNewSessionGetsADifferentToken(): void
    {
        $first = csrf_token();
        $_SESSION = [];

        $this->assertNotSame($first, csrf_token());
        $this->assertFalse(csrf_valid($first));
    }

    public function testHiddenFieldContainsTheToken(): void
    {
        $this->assertStringContainsString('value="' . csrf_token() . '"', csrf_field());
    }
}
