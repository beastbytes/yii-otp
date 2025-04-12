<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Migration\totp\uuid_pk;

use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M250404182335CreateTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    private const TABLE_NAME = 'totp';


    /**
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function up(MigrationBuilder $b): void
    {
        $b->createTable(
            self::TABLE_NAME,
            [
                'user_id' => 'string(255) NOT NULL',
                'secret' => 'binary NOT NULL',
                'digest' => 'string(255) NOT NULL',
                'digits' => 'integer NOT NULL',
                'period' => 'integer NOT NULL',
                'leeway' => 'integer NOT NULL',
                'last_code' => 'string(6) NOT NULL',
                'PRIMARY KEY ([[user_id]])',
            ],
        );
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