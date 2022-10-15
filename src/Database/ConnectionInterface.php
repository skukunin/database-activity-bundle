<?php

namespace SKukunin\DatabaseActivityBundle\Database;

interface ConnectionInterface
{
    public function insert(
        string $tableName,
        string $entityId,
        string $action,
        string $userId = '',
        string $columnName = '',
        string $oldValue = '',
        string $newValue = ''
    ): void;
}