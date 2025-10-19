<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Exception;
use Throwable;
use Yiisoft\Db\Exception\Exception as DbException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\Security\Random;

trait BackupCodeTrait
{
    /**
     * Number of backup codes to generate
     */
    public const BACKUP_CODE_COUNT = 10;
    /**
     * Length of each backup code
     */
    public const BACKUP_CODE_LENGTH = 16;
    /** @var string Backup codes only contain digits and upper and lowercase letters */
    public const BACKUP_CODE_REGEX = '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?!.*_)(?!.*\W)(?!.* ).+$/';

    /**
     * @throws DbException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function countBackupCodes(string $userId): int
    {
        return (new Query($this->database))
            ->from($this->backupCodeTable)
            ->where(['user_id' => $userId])
            ->count()
        ;
    }

    /**
     * @param string $userId
     * @return string[]
     * @throws Exception
     * @throws Throwable
     */
    public function createBackupCodes(string $userId): array
    {
        $this
            ->database
            ->createCommand()
            ->delete($this->backupCodeTable, ['user_id' => $userId])
            ->execute()
        ;

        $codes = [];
        $rows = [];
        $passwordHasher = new PasswordHasher();

        for ($i = 0; $i < $this->backupCodeCount; $i++) {
            do {
                $code = Random::string($this->backupCodeLength);
            } while (preg_match(self::BACKUP_CODE_REGEX, $code) === 0);

            $codes[] = $code;
            $rows[] = [
                $userId,
                $passwordHasher
                    ->hash($code)
                ,
            ];
        }

        $this
            ->database
            ->createCommand()
            ->insertBatch(
                $this->backupCodeTable,
                $rows,
                ['user_id', 'code'],
            )
            ->execute()
        ;

        return $codes;
    }

    /**
     * @throws DbException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function deleteBackupCodes(string $userId): void
    {
        $this
            ->database
            ->createCommand()
            ->delete($this->backupCodeTable, ['user_id' => $userId])
            ->execute()
        ;
    }

    /**
     * @throws DbException
     * @throws InvalidConfigException
     * @throws Throwable
     */
    private function verifyBackupCode(string $code, string $userId): bool
    {
        $rows = (new Query($this->database))
            ->from($this->backupCodeTable)
            ->where(['user_id' => $userId])
            ->all()
        ;

        foreach ($rows as $row) {
            if ((new PasswordHasher())->validate($code, $row['code'])) {
                $this
                    ->database
                    ->createCommand()
                    ->delete($this->backupCodeTable, ['id' => $row['id']])
                    ->execute()
                ;

                return true;
            }
        }

        return false;
    }
}