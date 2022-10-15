<?php

namespace SKukunin\DatabaseActivityBundle\Service;

interface ChangeLogServiceInterface
{
    public function logEntityInsert(string $tableName, string $entityId): void;

    public function logEntityUpdate(
        string $tableName,
        string $entityId,
        string $columnName,
        string $oldValue, string $newValue
    ): void;

    public function logEntityDelete(string $tableName, string $entityId): void;
}