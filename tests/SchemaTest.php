<?php

namespace BeastBytes\Yii\Otp\Tests;

use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use RuntimeException;
use Yiisoft\Db\Constraint\Constraint;

class SchemaTest extends TestCase
{
    use DatabaseTrait;

    #[Test]
    public function schema(): void
    {
        $this->checkNoTables();

        $this->runMigrations();
        $this->checkTables();

        $this->rollbackMigrations();
        $this->checkNoTables();
    }

    private function checkNoTables(): void
    {
        foreach (array_keys(self::$tableSchemas) as $tableName) {
            $this->assertNull(
                $this
                    ->getDatabase()
                    ->getSchema()
                    ->getTableSchema(self::$inflector->toSnakeCase($tableName))
            );
        }
    }

    private function checkTables(): void
    {
        $database = $this->getDatabase();
        $databaseSchema = $database->getSchema();

        foreach (self::$tableSchemas as $tableName => $columnSchemas) {
            $foreignKeys = $indexes = 0;
            $tableName = self::$inflector->toSnakeCase($tableName);
            $table = $databaseSchema->getTableSchema(self::$inflector->toSnakeCase($tableName));

            $this->assertNotNull($table, "No schema found for $tableName table");

            $columns = $table->getColumns();

            foreach ($columnSchemas as $name => $columnSchema) {
                $this->assertArrayHasKey($name, $columns);
                $column = $columns[$name];
                $this->assertSame($columnSchema['type'], $column->getType());
                if (array_key_exists('size', $columnSchema)) {
                    $this->assertSame($columnSchema['size'], $column->getSize());
                }
                $columnSchema['null']
                    ? $this->assertTrue($column->isAllowNull())
                    : $this->assertFalse($column->isAllowNull())
                ;

                if (array_key_exists('foreignKeys', $columnSchema) && $columnSchema['foreignKeys'] === true) {
                    $foreignKeys++;
                }

                if (array_key_exists('index', $columnSchema)) {
                    if (($columnSchema['index']['type']) === 'primary') {
                        $primaryKey = $databaseSchema->getTablePrimaryKey($tableName);
                        $this->assertInstanceOf(Constraint::class, $primaryKey);
                        $this->assertSame([$name], $primaryKey->getColumnNames());
                    } else {
                        $indexes++;
                        $this->assertIndex(
                            table: $tableName,
                            expectedColumnNames: [$name],
                            expectedName: $columnSchema['index']['name'],
                            expectedIsUnique: $columnSchema['index']['isUnique']
                        );
                    }
                }
            }

            $this->assertCount($foreignKeys, $databaseSchema->getTableForeignKeys($tableName));
            $this->assertCount($indexes, $databaseSchema->getTableIndexes($tableName));
        }
    }

    protected function assertIndex(
        string $table,
        array $expectedColumnNames,
        ?string $expectedName = null,
        bool $expectedIsUnique = false,
        bool $expectedIsPrimary = false,
    ): void
    {
        $indexes = $this
            ->getDatabase()
            ->getSchema()
            ->getTableIndexes($table)
        ;
        $found = false;
        foreach ($indexes as $index) {
            try {
                $this->assertEqualsCanonicalizing($expectedColumnNames, $index->getColumnNames());
            } catch (ExpectationFailedException) {
                continue;
            }

            $found = true;

            $this->assertSame($expectedIsUnique, $index->isUnique());
            $this->assertSame($expectedIsPrimary, $index->isPrimary());

            if ($expectedName !== null) {
                $this->assertSame($expectedName, $index->getName());
            }
        }

        if (!$found) {
            self::fail('Index not found.');
        }
    }
}