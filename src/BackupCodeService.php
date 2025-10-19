<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp;

use Throwable;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Query\Query;
use Yiisoft\Security\PasswordHasher;
use Yiisoft\Security\Random;

final class BackupCodeService implements BackupCodeServiceInterface
{
    /**
     * Number of backup codes to generate
     */
    public const COUNT = 10;
    /**
     * Length of each backup code
     */
    public const LENGTH = 16;
    /** @var string Backup codes only contain digits and upper and lowercase letters */
    public const REGEX = '/^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?!.*_)(?!.*\W)(?!.* ).+$/';

    /**
     * @param positive-int $count Number of backup codes to generate
     * @param positive-int $length Length of each backup code
     * @psalm-param non-empty-string $table Table name for backup codes
     * @param ConnectionInterface $database Yii database connection instance
     *
     */
    public function __construct(
        private readonly int $count,
        private readonly int $length,
        private readonly string $table,
        protected readonly ConnectionInterface $database,
    )
    {
    }

    /**
     * @return int The number of usable backup codes available to the user.
     */
    public function count(string $userId): int
    {
        return (new Query($this->database))
            ->from($this->table)
            ->where(['user_id' => $userId])
            ->count()
        ;
    }

    /**
     * @return string[]
     */
    public function create(string $userId): array
    {
        $this->delete($userId);

        $codes = [];
        $rows = [];
        $passwordHasher = new PasswordHasher();

        for ($i = 0; $i < $this->count; $i++) {
            do {
                $code = Random::string($this->length);
            } while (preg_match(self::REGEX, $code) === 0);

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
                $this->table,
                $rows,
                ['user_id', 'code'],
            )
            ->execute()
        ;

        return $codes;
    }

    /**
     * @throws InvalidConfigException
     * @throws Throwable
     * @throws Exception
     */
    public function delete(string $userId): int
    {
        return $this
            ->database
            ->createCommand()
            ->delete($this->table, ['user_id' => $userId])
            ->execute()
        ;
    }

    /**
     * @param string $code The code to verify against the user
     * @return bool Whether the code was valid for the user
     */
    public function verify(string $code, string $userId): bool
    {
        $rows = (new Query($this->database))
            ->from($this->table)
            ->where(['user_id' => $userId])
            ->all()
        ;

        foreach ($rows as $row) {
            if ((new PasswordHasher())->validate($code, $row['code'])) {
                $this
                    ->database
                    ->createCommand()
                    ->delete($this->table, ['id' => $row['id']])
                    ->execute()
                ;

                return true;
            }
        }

        return false;
    }
}