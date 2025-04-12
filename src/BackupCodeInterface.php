<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

interface BackupCodeInterface
{
    /**
     * @param string $userId
     * @return int The number of usable backup codes available to the user.
     */
    public function countBackupCodes(string $userId): int;

    /**
     * @param string $userId
     * @return string[]
     */
    public function createBackupCodes(string $userId): array;
}