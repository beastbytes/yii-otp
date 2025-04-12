<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Tests;

use BeastBytes\Yii\Otp\Hotp;
use BeastBytes\Yii\Otp\Otp;
use Generator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class HotpTest extends TestCase
{
    private const TEST_SECRET = 'JDDK4U6G3BJLEZ7Y';
    public const COUNTER = 1000;
    private const INVALID_OTP = '621254';
    private const VALID_OTP = '406569';

    private static array $params;
    private static Hotp $otp;

    #[BeforeClass]
    public static function init(): void
    {
        self::$params = require __DIR__ . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR . 'params.php';
    }

    #[Before]
    protected function before(): void
    {
        self::$otp = new Hotp(
            counter: self::$params['hotp']['counter'],
            window: self::$params['hotp']['window'],
            digest: self::$params['hotp']['digest'],
            digits: self::$params['hotp']['digits'],
            secretLength: self::$params['hotp']['secretLength'],
        );
    }

    #[Test]
    public function otpParameters(): void
    {
        $this->assertSame(
            self::$params['hotp']['counter'],
            self::$otp->getCounter()
        );
        $this->assertSame(
            self::$params['hotp']['window'],
            self::$otp->getWindow()
        );
        $this->assertSame(
            self::$params['hotp']['digest'],
            self::$otp->getDigest()
        );
        $this->assertSame(
            self::$params['hotp']['digits'],
            self::$otp->getDigits()
        );
        $this->assertMatchesRegularExpression('/^[2-7A-Z]+$/', self::$otp->getSecret());
    }

    #[Test]
    #[DataProvider('urlParameters')]
    public function provisioningUrl(?string $label, ?string $issuer, array $params, string $pattern): void
    {
        $actual = self::$otp->getProvisioningUri($label, $issuer, $params);
        $this->assertMatchesRegularExpression($pattern, $actual);
    }

    #[Test]
    public function verifyOtp(): void
    {
        $reflectionSecret = new ReflectionProperty(self::$otp, 'secret');
        $reflectionSecret->setValue(self::$otp, self::TEST_SECRET);

        $this->assertFalse(self::$otp->verify(self::INVALID_OTP));
        $this->assertTrue(self::$otp->verify(self::VALID_OTP));
        // Same code can not be valid twice
        $this->assertFalse(self::$otp->verify(self::VALID_OTP));
        $this->assertSame(self::VALID_OTP, self::$otp->getLastCode());
    }

    #[Test]
    public function verifyCounterChanged(): void
    {
        $reflectionSecret = new ReflectionProperty(self::$otp, 'secret');
        $reflectionSecret->setValue(self::$otp, self::TEST_SECRET);

        static::assertSame(self::$otp->getCounter(), self::$params['hotp']['counter']);
        $this->assertTrue(self::$otp->verify(self::VALID_OTP));
        static::assertSame(self::$otp->getCounter(), self::$params['hotp']['counter'] + 1);
        $this->assertFalse(self::$otp->verify(self::INVALID_OTP));
        static::assertSame(self::$otp->getCounter(), self::$params['hotp']['counter'] + 1);
    }

    public static function urlParameters(): Generator
    {
        yield [
            'label' => 'Otp Label',
            'issuer' => null,
            'params' => [],
            'pattern' => '|^'
                . 'otpauth://hotp/Otp%20Label'
                . '\?counter=\d+'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => 'Otp Issuer',
            'params' => [],
            'pattern' => '|^'
                . 'otpauth://hotp/Otp%20Issuer%3AOtp%20Label'
                . '\?counter=\d+'
                . '\&issuer=Otp%20Issuer'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => null,
            'params' => ['p1' => 'v1', 'p2' => 'v2'],
            'pattern' => '|^'
                . 'otpauth://hotp/Otp%20Label'
                . '\?counter=\d+'
                . '\&p1=v1'
                . '\&p2=v2'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => 'Otp Issuer',
            'params' => ['p1' => 'v1', 'p2' => 'v2'],
            'pattern' => '|^'
                . 'otpauth://hotp/Otp%20Issuer%3AOtp%20Label'
                . '\?counter=\d+'
                . '\&issuer=Otp%20Issuer'
                . '\&p1=v1'
                . '\&p2=v2'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
    }
}
