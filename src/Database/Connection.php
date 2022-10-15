<?php

namespace SKukunin\DatabaseActivityBundle\Database;

use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Types\Types;

class Connection implements ConnectionInterface
{
    protected const TABLE_OPTION_NAME = '_symfony_messenger_table_name';

    protected array $configuration = [];
    protected DBALConnection $driverConnection;
    private bool $autoSetup;

    public function __construct(DBALConnection $driverConnection, array $configuration)
    {
        $this->configuration = $configuration;
        $this->driverConnection = $driverConnection;
        $this->autoSetup = $this->configuration['auto_setup'];
    }

    public function insert(
        string $tableName,
        string $entityId,
        string $action,
        string $userId = '',
        string $columnName = '',
        string $oldValue = '',
        string $newValue = ''
    ): void
    {
        $qb = $this->driverConnection->createQueryBuilder();
        $qb
            ->insert($this->configuration['table_name'])
            ->values([
                'created_at' => '?',
                'table_name' => '?',
                'entity_id' => '?',
                'action' => '?',
                'field_name' => '?',
                'old_value' => '?',
                'new_value' => '?',
                'user_id' => '?'
            ])
        ;
        $this->executeStatement($qb->getSQL(), [
            new \DateTime(),
            $tableName,
            $entityId,
            $action,
            $columnName,
            $oldValue,
            $newValue,
            $userId
        ], [
            Types::DATETIME_MUTABLE,
            null,
            null,
            null,
            null,
            null
        ]);
    }

    private function setup(): void
    {
        $configuration = $this->driverConnection->getConfiguration();
        $assetFilter = $configuration->getSchemaAssetsFilter();
        $configuration->setSchemaAssetsFilter(null);
        $this->updateSchema();
        $configuration->setSchemaAssetsFilter($assetFilter);
        $this->autoSetup = false;
    }

    private function updateSchema(): void
    {
        $schemaManager = $this->createSchemaManager();
        $comparator = $this->createComparator($schemaManager);
        $schemaDiff = $this->compareSchemas($comparator, $schemaManager->createSchema(), $this->getSchema());

        foreach ($schemaDiff->toSaveSql($this->driverConnection->getDatabasePlatform()) as $sql) {
            if (method_exists($this->driverConnection, 'executeStatement')) {
                $this->driverConnection->executeStatement($sql);
            } else {
                $this->driverConnection->exec($sql);
            }
        }
    }

    private function createSchemaManager(): AbstractSchemaManager
    {
        return method_exists($this->driverConnection, 'createSchemaManager')
            ? $this->driverConnection->createSchemaManager()
            : $this->driverConnection->getSchemaManager();
    }

    private function createComparator(AbstractSchemaManager $schemaManager): Comparator
    {
        return method_exists($schemaManager, 'createComparator')
            ? $schemaManager->createComparator()
            : new Comparator();
    }

    private function compareSchemas(Comparator $comparator, Schema $from, Schema $to): SchemaDiff
    {
        return method_exists($comparator, 'compareSchemas')
            ? $comparator->compareSchemas($from, $to)
            : $comparator->compare($from, $to);
    }

    private function getSchema(): Schema
    {
        $schema = new Schema([], [], $this->createSchemaManager()->createSchemaConfig());
        $this->addTableToSchema($schema);

        return $schema;
    }

    private function addTableToSchema(Schema $schema): void
    {
        $table = $schema->createTable($this->configuration['table_name']);
        // add an internal option to mark that we created this & the non-namespaced table name
        $table->addOption(self::TABLE_OPTION_NAME, $this->configuration['table_name']);
        $table
            ->addColumn('id', Types::BIGINT)
            ->setAutoincrement(true)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('created_at', Types::DATETIME_MUTABLE)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('table_name', Types::STRING)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('entity_id', Types::STRING)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('action', Types::STRING)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('field_name', Types::STRING)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('old_value', Types::TEXT)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('new_value', Types::TEXT)
            ->setNotnull(true)
        ;
        $table
            ->addColumn('user_id', Types::STRING)
            ->setLength(190) // MySQL 5.6 only supports 191 characters on an indexed column in utf8mb4 mode
            ->setNotnull(true)
        ;

        $table->setPrimaryKey(['id']);
        $table->addIndex(['user_id']);
        $table->addIndex(['created_at']);
        $table->addIndex(['field_name']);
    }

    public function executeStatement(string $sql, array $parameters = [], array $types = [])
    {
        try {
            if (method_exists($this->driverConnection, 'executeStatement')) {
                $stmt = $this->driverConnection->executeStatement($sql, $parameters, $types);
            } else {
                $stmt = $this->driverConnection->executeUpdate($sql, $parameters, $types);
            }
        } catch (TableNotFoundException $e) {
            if ($this->driverConnection->isTransactionActive()) {
                throw $e;
            }

            // create table
            if ($this->autoSetup) {
                $this->setup();
            }
            if (method_exists($this->driverConnection, 'executeStatement')) {
                $stmt = $this->driverConnection->executeStatement($sql, $parameters, $types);
            } else {
                $stmt = $this->driverConnection->executeUpdate($sql, $parameters, $types);
            }
        }

        return $stmt;
    }
}