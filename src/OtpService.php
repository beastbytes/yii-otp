<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use chillerlan\QRCode\QRCode;
use ReflectionException;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Security\Crypt;

abstract class OtpService implements OtpServiceInterface, BackupCodeInterface
{
    use BackupCodeTrait;

    /**
     * Derivation iterations count
     */
    public const CRYPT_ITERATIONS = 100000;

    /**
     * @param positive-int $backupCodeCount Number of backup codes to generate
     * @param positive-int $backupCodeLength Length of each backup code
     * @param Crypt $crypt Crypt instance
     * @param ConnectionInterface $database Yii database connection instance
     * @psalm-param non-empty-string $encryptionKey Key for encryption and decryption
     * @param OtpInterface $otp OTP instance
     * @psalm-param non-empty-string $backupCodeTable Table name for OTP backup codes
     * @psalm-param non-empty-string $otpTable Table name for OTP codes and data
     */
    public function __construct(
        private readonly int $backupCodeCount,
        private readonly int $backupCodeLength,
        private readonly string $backupCodeTable,
        protected readonly Crypt $crypt,
        protected readonly ConnectionInterface $database,
        protected readonly string $encryptionKey,
        protected readonly OtpInterface $otp,
        protected readonly string $otpTable,
    )
    {
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    public function createOtp(string $userId, string $label, ?string $issuer = null, array $params = []): array
    {
        $this
            ->database
            ->createCommand()
            ->insert(
                $this->otpTable,
                $this->columns($userId)
            )
            ->execute()
        ;

        return [
            'qrCode' => (new QRCode())->render(
                $this->otp->getProvisioningUri($label, $issuer, $params)
            ),
            'secret' => $this->otp->getSecret(),
        ];
    }

    /**
     * Disable OTP for a user.
     *
     * @param string $userId ID of the user.
     * @return void
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function disableOtp(string $userId): void
    {
        $this
            ->database
            ->createCommand()
            ->delete($this->otpTable, ['user_id' => $userId])
            ->execute()
        ;
        $this->deleteBackupCodes($userId);
    }

    /**
     * Return the OTP model for a user
     *
     * @param string $userId ID of the user.
     * @return OTP model for the user; null if OTP not enabled for the user
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getOtp(string $userId): ?OtpInterface
    {
        $row = (new Query($this->database))
            ->from($this->otpTable)
            ->where(['user_id' => $userId])
            ->one()
        ;

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row, $userId);
    }

    /**
     * Whether OTP is enabled for a user.
     *
     * @param string $userId ID of the user to check.
     * @return bool true if OTP is enabled, false if OTP is not enabled.
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function isOtpEnabled(string $userId): bool
    {
        return (new Query($this->database))
            ->from($this->otpTable)
            ->where(['user_id' => $userId])
            ->count()
        === 1;
    }

    /**
     * Verifies an OTP or backup code.
     * If a backup code is being verified and verification is successful, the backup code is deleted to prevent reuse.
     *
     * @param string $code Code to verify.
     * @param string $userId ID of the user.
     * @return bool true is verification is successful, false if it fails
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function verify(string $code, string $userId): bool
    {
        if (preg_match(self::BACKUP_CODE_REGEX, $code) === 1) {
            return $this->verifyBackupCode($code, $userId);
        }

        return $this->verifyOtpCode($code, $userId);
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function verifyOtpCode(string $code, string $userId): bool
    {
        $otp = $this->getOtp($userId);

        if ($otp->verify($code)) {
            $this
                ->database
                ->createCommand()
                ->update(
                    $this->otpTable,
                    $this->columns($userId),
                    [
                        'user_id' => $userId,
                    ],
                )
                ->execute()
            ;

            return true;
        }

        return false;
    }

    abstract protected function columns(string $userId): array;
    abstract protected function hydrate(array $data, string $userId): ?OtpInterface;
}
