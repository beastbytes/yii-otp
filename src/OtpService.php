<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use chillerlan\QRCode\QRCode;
use Override;
use ReflectionException;
use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\Query;

abstract class OtpService implements OtpServiceInterface
{
    /**
     * @param BackupCodeService $backupCodeService Backup code service
     * @param ConnectionInterface $database Yii database connection instance
     * @param OtpInterface $otp OTP instance
     * @psalm-param non-empty-string $table Table name for OTP codes and data
     */
    public function __construct(
        private readonly BackupCodeService $backupCodeService,
        private readonly ConnectionInterface $database,
        protected readonly OtpInterface $otp,
        private readonly string $table,
    )
    {
    }

    /**
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Throwable
     */
    #[Override]
    public function create(string $userId, string $label, ?string $issuer = null, array $params = []): array
    {
        $this
            ->database
            ->createCommand()
            ->insert(
                $this->table,
                $this->columns($userId)
            )
            ->execute()
        ;

        return [
            (new QRCode())->render(
                $this->otp->getProvisioningUri($label, $issuer, $params)
            ),
            $this->otp->getSecret(),
        ];
    }

    /**
     * Disable OTP for the user.
     * @return void
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    #[Override]
    public function disable(string $userId): void
    {
        $this
            ->database
            ->createCommand()
            ->delete($this->table, ['user_id' => $userId])
            ->execute()
        ;
        $this
            ->backupCodeService
            ->delete($userId)
        ;
    }

    /**
     * Whether OTP is enabled for the user.
     * @return bool true if OTP is enabled, false if OTP is not enabled.
     * @throws Exception
     * @throws InvalidConfigException
     * @throws Throwable
     */
    #[Override]
    public function isEnabled(string $userId): bool
    {
        return (new Query($this->database))
            ->from($this->table)
            ->where(['user_id' => $userId])
            ->count()
        === 1;
    }

    /**
     * Verifies an OTP or backup code.
     * If a backup code is being verified and verification is successful, the backup code is deleted to prevent reuse.
     * @param string $code Code to verify.
     * @return bool true is verification is successful, false if it fails
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    #[Override]
    public function verify(string $code, string $userId): bool
    {
        if (preg_match(BackupCodeService::REGEX, $code) === 1) {
            return $this
                ->backupCodeService
                ->verify($code, $userId)
            ;
        }

        $otp = $this->getOtp($userId);

        if ($otp?->verify($code)) {
            $this
                ->database
                ->createCommand()
                ->update(
                    $this->table,
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

    /**
     * Return the OTP model for the user.
     * @return ?OtpInterface OTP model for the user; null if OTP not enabled for the user
     * @throws Exception
     * @throws InvalidConfigException
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function getOtp(string $userId): ?OtpInterface
    {
        $row = (new Query($this->database))
            ->from($this->table)
            ->where(['user_id' => $userId])
            ->one()
        ;

        if ($row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    #[Override]
    abstract public function getParameters(string $userId): array;
    abstract protected function columns(string $userId): array;
    abstract protected function hydrate(array $data): ?OtpInterface;
}