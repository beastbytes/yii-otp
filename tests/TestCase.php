<?php

declare(strict_types=1);

namespace BeastBytes\Yii\Otp\Tests;

use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Migration\Command\DownCommand;
use Yiisoft\Db\Migration\Command\UpdateCommand;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;
use Yiisoft\Injector\Injector;
use Yiisoft\Strings\Inflector;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public const BACKUP_CODE_COUNT = 10;
    public const BACKUP_CODE_LENGTH = 16;

    protected static ?ConnectionInterface $database = null;
    private ?DownCommand $migrateDownCommand = null;
    private ?UpdateCommand $migrateUpdateCommand = null;

    protected static Inflector $inflector;
    protected static array $params;

    protected static array $tableSchemas = [
        'hotp' => [
            'user_id' => [
                'index' => [
                    'type' => 'primary',
                ],
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'secret' => [
                'type' => 'string',
                'size' => 255,
                'null' => false,
            ],
            'digest' => [
                'type' => 'string',
                'size' => 255,
                'null' => false,
            ],
            'digits' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'counter' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'window' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'last_code' => [
                'type' => 'string',
                'size' => 6,
                'null' => false,
            ],
        ],
        'totp' => [
            'user_id' => [
                'index' => [
                    'type' => 'primary',
                ],
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'secret' => [
                'type' => 'string',
                'size' => 255,
                'null' => false,
            ],
            'digest' => [
                'type' => 'string',
                'size' => 255,
                'null' => false,
            ],
            'digits' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'leeway' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'period' => [
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'last_code' => [
                'type' => 'string',
                'size' => 6,
                'null' => false,
            ],
        ],
        'otpBackupCode' => [
            'id' => [
                'index' => [
                    'type' => 'primary',
                ],
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'user_id' => [
                'index' => [
                    'type' => 'index',
                    'name' => 'idx-otp_backup_codes-user_id',
                    'isUnique' => false,
                ],
                'type' => 'integer',
                'size' => null,
                'null' => false,
            ],
            'code' => [
                'type' => 'string',
                'size' => 255,
                'null' => false,
            ],
        ],
    ];

    #[BeforeClass]
    public static function init(): void
    {
        self::$inflector = new Inflector();
        self::$params = require __DIR__ . DIRECTORY_SEPARATOR . 'support' . DIRECTORY_SEPARATOR . 'params.php';
    }

    protected function getDatabase(): ConnectionInterface
    {
        if (self::$database === null) {
            self::$database = $this->makeDatabase();
        }

        return self::$database;
    }

    protected function getMigrateUpdateCommand(string $ns): UpdateCommand
    {
        if ($this->migrateUpdateCommand !== null) {
            return $this->migrateUpdateCommand;
        }

        $migrator = new Migrator($this->getDatabase(), new NullMigrationInformer());
        $this->migrateUpdateCommand = new UpdateCommand(
            new UpdateRunner($migrator),
            $this->getMigrationService($migrator, $ns),
            $migrator
        );
        $this->migrateUpdateCommand->setHelperSet(new HelperSet([
            'question' => new QuestionHelper(),
        ]));

        return $this->migrateUpdateCommand;
    }

    protected function getMigrateDownCommand(string $ns): DownCommand
    {
        if ($this->migrateDownCommand !== null) {
            return $this->migrateDownCommand;
        }

        $migrator = new Migrator($this->getDatabase(), new NullMigrationInformer());
        $this->migrateDownCommand = new DownCommand(
            new DownRunner($migrator),
            $this->getMigrationService($migrator, $ns),
            $migrator
        );
        $this->migrateDownCommand->setHelperSet(new HelperSet([
            'question' => new QuestionHelper(),
        ]));

        return $this->migrateDownCommand;
    }

    public static function tearDownAfterClass(): void
    {
        (new static(static::class))->rollbackMigrations();
    }

    protected function runMigrations(): void
    {
        $input = new ArrayInput([]);
        $input->setInteractive(false);

        foreach (array_keys(self::$tableSchemas) as $ns) {
            $this->getMigrateUpdateCommand($ns)->run($input, new NullOutput());
        }
    }

    protected function rollbackMigrations(): void
    {
        $input = new ArrayInput(['--all' => true]);
        $input->setInteractive(false);

        foreach (array_keys(self::$params) as $ns) {
            $this->getMigrateDownCommand($ns)->run($input, new NullOutput());
        }
    }

    private function getMigrationService(Migrator $migrator, string $ns): MigrationService
    {
        $namespaces = [];

        foreach (array_keys(self::$tableSchemas) as $ns) {
            $namespaces[] = 'BeastBytes\\Yii\\Otp\\Migration\\' . $ns . '\\int_pk';
        }

        $migrationService = new MigrationService($this->getDatabase(), new Injector(), $migrator);
        $migrationService->setSourceNamespaces($namespaces);

        return $migrationService;
    }

    abstract protected function makeDatabase(): ConnectionInterface;
}