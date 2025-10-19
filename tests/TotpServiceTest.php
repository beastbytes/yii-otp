<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Tests;

use BeastBytes\Yii\Otp\BackupCodeService;
use BeastBytes\Yii\Otp\Totp;
use BeastBytes\Yii\Otp\TotpService;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Symfony\Component\Clock\MockClock;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

class TotpServiceTest extends TestCase
{
    use DatabaseTrait;

    private const TEST_SECRET = 'JDDK4U6G3BJLEZ7Y';
    private const EPOCH = 319690800;
    private const INVALID_OTP = '621254';
    private const VALID_OTP = '762124';

    private const LABEL = 'TOTP Test';
    private const ISSUER = 'PhpUnit';
    private const QR_CODE_REGEX = '/^data:image\/svg\+xml;base64,[\da-zA-Z]+(\+[\da-zA-Z]+)+=+$/';
    private const USER_ID = '35';

    private static Totp $otp;

    private BackupCodeService $backupCodeService;
    private TotpService $otpService;

    /**
     * @throws DateMalformedStringException
     * @throws DateInvalidTimeZoneException
     */
    #[Before]
    protected function before(): void
    {
        $database = $this->getDatabase();
        $this->runMigrations();

        self::$otp = new Totp(
            clock: new MockClock((new DateTimeImmutable())->setTimestamp(self::EPOCH)),
            period: self::$params['totp']['period'],
            leeway: self::$params['totp']['leeway'],
            digest: self::$params['totp']['digest'],
            digits: self::$params['totp']['digits'],
            secretLength: self::$params['totp']['secretLength'],
        );
        $this->backupCodeService = new BackupCodeService(
            count: self::$params['backupCode']['count'],
            length: self::$params['backupCode']['length'],
            table: self::$params['database']['backupCodeTable'],
            database: $database,
        );
        $this->otpService = new TotpService(
            backupCodeService: $this->backupCodeService,
            database: $database,
            otp: self::$otp,
            table: self::$params['database']['totpTable'],
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws Exception
     */
    #[After]
    protected function after(): void
    {
        $this
            ->otpService
            ->disable(self::USER_ID)
        ;
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    public function createOtp(): void
    {
        $this->assertFalse(
            $this
                ->otpService
                ->isEnabled(self::USER_ID),
        );

        $result = $this
            ->otpService
            ->create(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsString($result[1]);
        $this->assertMatchesRegularExpression(self::QR_CODE_REGEX, $result[0]);
        $this->assertMatchesRegularExpression('/^[2-7A-Z]+$/', $result[1]);

        $this->assertTrue(
            $this
                ->otpService
                ->isEnabled(self::USER_ID),
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     * @throws Throwable
     */
    #[Test]
    public function disableOtp()
    {
        $this
            ->otpService
            ->create(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertTrue(
            $this
                ->otpService
                ->isEnabled(self::USER_ID),
        );

        $this
            ->otpService
            ->disable(self::USER_ID)
        ;

        $this->assertFalse(
            $this
                ->otpService
                ->isEnabled(self::USER_ID),
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     * @throws Throwable
     * @throws \ReflectionException
     */
    #[Test]
    public function otpParameters(): void
    {
        $this->assertEmpty(
            $this
                ->otpService
                ->getParameters(self::USER_ID)
        );

        $this
            ->otpService
            ->create(self::USER_ID, self::LABEL)
        ;

        $otpParameters = $this
            ->otpService
            ->getParameters(self::USER_ID)
        ;
        $this->assertNotEmpty($otpParameters);

        foreach ([
            'digest' => 'string',
            'digits' => 'int',
            'leeway' => 'int',
            'period' => 'int',
        ] as $parameter => $type) {
            $this->assertArrayHasKey($parameter, $otpParameters);
            $assertion = 'assertIs' . ucfirst($type);
            $this->$assertion($otpParameters[$parameter]);
        }
    }

    #[Test]
    public function verifyOtp(): void
    {
        $reflectionSecret = new ReflectionProperty(self::$otp, 'secret');
        $reflectionSecret->setValue(self::$otp, self::TEST_SECRET);

        $totpService = new TotpService(
            backupCodeService: $this->backupCodeService,
            database: $this->getDatabase(),
            otp: self::$otp,
            table: self::$params['database']['totpTable'],
        );

        $totpService
            ->create(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertFalse($totpService->verify(self::INVALID_OTP, self::USER_ID));
        $this->assertTrue($totpService->verify(self::VALID_OTP, self::USER_ID));
        // Same code can not be valid twice
        $this->assertFalse($totpService->verify(self::VALID_OTP, self::USER_ID));
    }
}
