<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Tests;

use BeastBytes\Yii\Otp\Otp;
use BeastBytes\Yii\Otp\Totp;
use Generator;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Clock\MockClock;

class TotpTest extends TestCase
{
    private const TEST_SECRET = 'JDDK4U6G3BJLEZ7Y';
    private const EPOCH = 319690800;
    private const INVALID_OTP = '621254';
    private const VALID_OTP = '762124';

    private static array $params;
    private static Totp $otp;

    #[BeforeClass]
    public static function init(): void
    {
        self::$params = require __DIR__ . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR . 'params.php';
    }

    #[Before]
    protected function before(): void
    {
        self::$otp = new Totp(
            clock: new MockClock((new \DateTimeImmutable())->setTimestamp(self::EPOCH)),
            period: self::$params['totp']['period'],
            leeway: self::$params['totp']['leeway'],
            digest: self::$params['totp']['digest'],
            digits: self::$params['totp']['digits'],
            secretLength: self::$params['totp']['secretLength'],
        );
    }

    #[Test]
    public function otpParameters(): void
    {
        $this->assertSame(
            self::$params['totp']['period']
            , self::$otp->getPeriod()
        );
        $this->assertSame(
            self::$params['totp']['leeway'],
            self::$otp->getLeeway()
        );
        $this->assertSame(
            self::$params['totp']['digest'],
            self::$otp->getDigest()
        );
        $this->assertSame(
            self::$params['totp']['digits'],
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

    public static function urlParameters(): Generator
    {
        yield [
            'label' => 'Otp Label',
            'issuer' => null,
            'params' => [],
            'pattern' => '|^'
                . 'otpauth://totp/Otp%20Label'
                . '\?secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => 'Otp Issuer',
            'params' => [],
            'pattern' => '|^'
                . 'otpauth://totp/Otp%20Issuer%3AOtp%20Label'
                . '\?issuer=Otp%20Issuer'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => null,
            'params' => ['p1' => 'v1', 'p2' => 'v2'],
            'pattern' => '|^'
                . 'otpauth://totp/Otp%20Label'
                . '\?p1=v1'
                . '\&p2=v2'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
        yield [
            'label' => 'Otp Label',
            'issuer' => 'Otp Issuer',
            'params' => ['p1' => 'v1', 'p2' => 'v2'],
            'pattern' => '|^'
                . 'otpauth://totp/Otp%20Issuer%3AOtp%20Label'
                . '\?issuer=Otp%20Issuer'
                . '\&p1=v1'
                . '\&p2=v2'
                . '\&secret=[2-7A-Z]+'
                . '$|'
        ];
    }
}
