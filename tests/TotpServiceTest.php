<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Tests;

use BeastBytes\Yii\Otp\Totp;
use BeastBytes\Yii\Otp\TotpService;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Symfony\Component\Clock\MockClock;
use Yiisoft\Security\Crypt;
use Yiisoft\Security\Random;

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
    public const SECRET_REGEX = '/^[2-7A-Z]+$/';
    private const USER_ID = '35';

    private static Crypt $crypt;
    private static Totp $otp;

    private TotpService $otpService;

    #[BeforeClass]
    public static function init(): void
    {
        parent::init();
        self::$crypt = new Crypt(self::$params['crypt']['cipher']);
    }

    #[Before]
    protected function before(): void
    {
        $database = $this->getDatabase();
        $this->runMigrations();

        self::$otp = new Totp(
            clock: new MockClock((new \DateTimeImmutable())->setTimestamp(self::EPOCH)),
            digest: self::$params['totp']['digest'],
            digits: self::$params['totp']['digits'],
            leeway: self::$params['totp']['leeway'],
            period: self::$params['totp']['period'],
            secretLength: self::$params['totp']['secretLength'],
        );
        $this->otpService = new TotpService(
            backupCodeCount: self::$params['backupCode']['count'],
            backupCodeLength: self::$params['backupCode']['length'],
            crypt: self::$crypt,
            database: $database,
            encryptionKey: self::$params['crypt']['key'],
            backupCodeTable: self::$params['database']['otpBackupCodeTable'],
            otp: self::$otp,
            otpTable: self::$params['database']['totpTable'],
        );
    }

    #[After]
    protected function after(): void
    {
        $this
            ->otpService
            ->disableOtp(self::USER_ID)
        ;
    }

    #[Test]
    public function backupCodes(): void
    {
        $this->assertSame(
            0,
            $this->otpService->countBackupCodes(self::USER_ID),
        );

        $backupCodes = $this
            ->otpService
            ->createBackupCodes(self::USER_ID)
        ;

        $this->assertCount(
            self::$params['backupCode']['count'],
            $backupCodes,
        );

        foreach ($backupCodes as $backupCode) {
            $this->assertIsString($backupCode);
            $this->assertMatchesRegularExpression(
                '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?!.*_)(?!.*\W)(?!.* ).{'
                    . self::$params['backupCode']['length']
                    .'}$/',
                $backupCode,
            );
        }

        do {
            $invalidBackupCode = Random::string(self::$params['backupCode']['length']);
        } while (
            in_array($invalidBackupCode, $backupCodes)
            || preg_match(TotpService::BACKUP_CODE_REGEX, $invalidBackupCode) === 0)
        ;

        $this->assertFalse(
            $this
                ->otpService
                ->verify($invalidBackupCode, self::USER_ID),
        );

        $backupCode = $backupCodes[array_rand($backupCodes)];

        $this->assertTrue(
            $this
                ->otpService
                ->verify($backupCode, self::USER_ID),
        );

        $this->assertFalse(
            $this
                ->otpService
                ->verify($backupCode, self::USER_ID),
        );

        $this->assertSame(
            self::$params['backupCode']['count'] - 1,
            $this->otpService->countBackupCodes(self::USER_ID),
        );

        $this->assertCount(
            self::$params['backupCode']['count'],
            $this
                ->otpService
                ->createBackupCodes(self::USER_ID),
        );
    }

    #[Test]
    public function createOtp(): void
    {
        $this->assertFalse(
            $this
                ->otpService
                ->isOtpEnabled(self::USER_ID),
        );

        $result = $this
            ->otpService
            ->createOtp(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertCount(2, $result);
        $this->assertIsString($result[0]);
        $this->assertIsString($result[1]);
        $this->assertMatchesRegularExpression(self::QR_CODE_REGEX, $result[0]);
        $this->assertMatchesRegularExpression('/^[2-7A-Z]+$/', $result[1]);

        $this->assertTrue(
            $this
                ->otpService
                ->isOtpEnabled(self::USER_ID),
        );
    }

    #[Test]
    public function disableOtp()
    {
        $this
            ->otpService
            ->createOtp(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertTrue(
            $this
                ->otpService
                ->isOtpEnabled(self::USER_ID),
        );

        $this
            ->otpService
            ->disableOtp(self::USER_ID)
        ;

        $this->assertFalse(
            $this
                ->otpService
                ->isOtpEnabled(self::USER_ID),
        );
    }

    #[Test]
    public function otpParameters(): void
    {
        $this->assertEmpty($this->otpService->getOtpParameters(self::USER_ID));

        $this->otpService->createOtp(self::USER_ID, self::LABEL);

        $otpParameters = $this->otpService->getOtpParameters(self::USER_ID);
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
            backupCodeCount: self::$params['backupCode']['count'],
            backupCodeLength: self::$params['backupCode']['length'],
            crypt: self::$crypt,
            database: $this->getDatabase(),
            encryptionKey: self::$params['crypt']['key'],
            backupCodeTable: self::$params['database']['otpBackupCodeTable'],
            otp: self::$otp,
            otpTable: self::$params['database']['totpTable'],
        );

        $totpService
            ->createOtp(self::USER_ID, self::LABEL, self::ISSUER)
        ;

        $this->assertFalse($totpService->verify(self::INVALID_OTP, self::USER_ID));
        $this->assertTrue($totpService->verify(self::VALID_OTP, self::USER_ID));
        // Same code can not be valid twice
        $this->assertFalse($totpService->verify(self::VALID_OTP, self::USER_ID));
    }
}
