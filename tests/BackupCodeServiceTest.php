<?php

declare(strict_types=1);


use BeastBytes\Yii\Otp\BackupCodeService;
use BeastBytes\Yii\Otp\Tests\DatabaseTrait;
use BeastBytes\Yii\Otp\Tests\TestCase;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\Test;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Security\Random;

class BackupCodeServiceTest extends TestCase
{
    use DatabaseTrait;

    private const USER_ID = '35';

    private BackupCodeService $backupCodeService;

    #[Before]
    protected function before(): void
    {
        $database = $this->getDatabase();
        $this->runMigrations();

        $this->backupCodeService = new BackupCodeService(
            count: self::$params['backupCode']['count'],
            length: self::$params['backupCode']['length'],
            table: self::$params['database']['backupCodeTable'],
            database: $database,
        );
    }

    /**
     * @throws Exception
     * @throws Throwable
     * @throws InvalidConfigException
     * @throws \ReflectionException
     */
    #[Test]
    public function backupCodes(): void
    {
        $this->assertSame(
            0,
            $this
                ->backupCodeService
                ->count(self::USER_ID)
        );

        $backupCodes = $this
            ->backupCodeService
            ->create(self::USER_ID)
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
            || preg_match(BackupCodeService::REGEX, $invalidBackupCode) === 0)
        ;

        $this->assertFalse(
            $this
                ->backupCodeService
                ->verify($invalidBackupCode, self::USER_ID),
        );

        $backupCode = $backupCodes[array_rand($backupCodes)];

        $this->assertTrue(
            $this
                ->backupCodeService
                ->verify($backupCode, self::USER_ID),
        );

        $this->assertFalse(
            $this
                ->backupCodeService
                ->verify($backupCode, self::USER_ID),
        );

        $this->assertSame(
            self::$params['backupCode']['count'] - 1,
            $this
                ->backupCodeService
                ->count(self::USER_ID),
        );

        $this->assertCount(
            self::$params['backupCode']['count'],
            $this
                ->backupCodeService
                ->create(self::USER_ID),
        );

        $this
            ->backupCodeService
            ->delete(self::USER_ID)
        ;

        $this->assertSame(
            0,
            $this
                ->backupCodeService
                ->count(self::USER_ID)
        );
    }
}