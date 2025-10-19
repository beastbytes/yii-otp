<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

interface BackupCodeServiceInterface
{
    /**
     * @return int The number of usable backup codes available to the user.
     */
    public function count(string $userId): int;

    /**
     * @return string[]
     */
    public function create(string $userId): array;

    /**
     * @return int Number of backup codes deleted
     */
    public function delete(string $userId): int;

    /**
     * @param string $code The code to verify against the user
     * @return bool Whether the code was valid for the user
     */
    public function verify(string $code, string $userId): bool;
}