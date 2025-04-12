<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Migration\otpBackupCode\uuid_pk;

use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M250404162335CreateTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    private const TABLE_NAME = 'otp_backup_code';


    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function up(MigrationBuilder $b): void
    {
        $b->createTable(
            self::TABLE_NAME,
            [
                'id' => 'string(255) NOT NULL',
                'user_id' => 'string(255) NOT NULL',
                'code' => 'string(255) NOT NULL',
                'PRIMARY KEY ([[id]])',
            ],
        );
        $b->createIndex(self::TABLE_NAME, 'idx-otp_backup_codes-user_id', 'user_id');
    }

    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function down(MigrationBuilder $b): void
    {
        $b->dropTable(self::TABLE_NAME);
    }
}