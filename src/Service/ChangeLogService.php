<?php

namespace SKukunin\DatabaseActivityBundle\Service;

use SKukunin\DatabaseActivityBundle\Database\ConnectionInterface;
use Symfony\Component\Security\Core\Security;

class ChangeLogService implements ChangeLogServiceInterface
{
    public const ACTION_UPDATE = 'update';
    public const ACTION_ADD = 'add';
    public const ACTION_DELETE = 'delete';

    private ConnectionInterface $connection;
    private ?Security $security;

    public function __construct(ConnectionInterface $connection, Security $security = null)
    {
        $this->connection = $connection;
        $this->security = $security;
    }

    /**
     * @param Security $security
     */
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    public function logEntityInsert(string $tableName, string $entityId): void
    {
        $userId = $this->getUserId();
        $this->connection->insert($tableName, $entityId, self::ACTION_ADD, $userId);
    }

    public function logEntityUpdate(
        string $tableName,
        string $entityId,
        string $columnName,
        string $oldValue,
        string $newValue
    ): void
    {
        $userId = $this->getUserId();
        $this->connection->insert(
            $tableName,
            $entityId,
            self::ACTION_UPDATE,
            $userId,
            $columnName,
            $oldValue, $newValue
        );
    }

    public function logEntityDelete(string $tableName, string $entityId): void
    {
        $userId = $this->getUserId();
        $this->connection->insert($tableName, $entityId, self::ACTION_DELETE, $userId);
    }

    private function getUserId(): string
    {
        $defaultUserId = '-';

        if (!$this->security instanceof Security) {
            return $defaultUserId;
        }

        return $this->security->getUser() ? $this->security->getUser()->getUserIdentifier() : $defaultUserId;
    }
}