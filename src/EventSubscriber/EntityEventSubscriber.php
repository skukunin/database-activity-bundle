<?php

namespace SKukunin\DatabaseActivityBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use SKukunin\DatabaseActivityBundle\Service\ChangeLogServiceInterface;
use Symfony\Component\Security\Core\Security;

class EntityEventSubscriber implements EventSubscriberInterface
{
    private const DATETIME_FORMAT = 'Y-m-d H:i:s';

    private ChangeLogServiceInterface $changeLogService;

    public function __construct(ChangeLogServiceInterface $changeLogService)
    {
        $this->changeLogService = $changeLogService;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entityInsertions = $uow->getScheduledEntityInsertions();
        $entityUpdates = $uow->getScheduledEntityUpdates();
        $entityDeletions = $uow->getScheduledEntityDeletions();
        $entityColUpdates = $uow->getScheduledCollectionUpdates();
        $entityColDeletions = $uow->getScheduledCollectionDeletions();

        foreach ($entityInsertions as $entity) {
            // get metadata
            $entityClassName = get_class($entity);
            $metaData = $em->getClassMetadata($entityClassName);

            // get entity id
            $entityId = $this->getEntityId($uow, $entity);

            // get table name
            $tableName = $metaData->getTableName();

            $this->changeLogService->logEntityInsert($tableName, $entityId);
        }

        foreach ($entityUpdates as $entity) {
            $changeSet = $uow->getEntityChangeSet($entity);

            // get metadata
            $entityClassName = get_class($entity);
            $metaData = method_exists($uow, 'getClassMetadata') ?
                $uow->getClassMetadata($entityClassName) :
                $em->getClassMetadata($entityClassName)
            ;

            // get entity id
            $entityId = $this->getEntityId($uow, $entity);

            // get table name
            $tableName = $metaData->getTableName();

            foreach ($changeSet as $fieldName => $changes) {
                $oldValue = array_key_exists(0, $changes) ? $changes[0] : null;
                $newValue = array_key_exists(1, $changes) ? $changes[1] : null;
                $columnName = $metaData->getFieldMapping($fieldName)['columnName'];

                if ($oldValue !== $newValue) {
                    $oldValue = $this->convertValueToString($oldValue);
                    $newValue = $this->convertValueToString($newValue);

                    $this->changeLogService->logEntityUpdate(
                        $tableName,
                        $entityId,
                        $columnName,
                        $oldValue,
                        $newValue
                    );
                }
            }
        }

        foreach ($entityDeletions as $entity) {
            // get metadata
            $entityClassName = get_class($entity);
            $metaData = $em->getClassMetadata($entityClassName);

            // get entity id
            $entityId = $this->getEntityId($uow, $entity);

            // get table name
            $tableName = $metaData->getTableName();

            $this->changeLogService->logEntityDelete($tableName, $entityId);
        }

        foreach ($entityColUpdates as $col) {

        }

        foreach ($entityColDeletions as $col) {

        }
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @param $entity
     * @return integer
     */
    private function getEntityId(UnitOfWork $unitOfWork, $entity)
    {
        $identifier = $unitOfWork->getEntityIdentifier($entity);
        $idFieldName = array_key_first($identifier);

        return $identifier[$idFieldName];
    }

    public function convertValueToString($value): string
    {
        if ($value instanceof \DateTime) {
            $value = $value->format(self::DATETIME_FORMAT);
        }

        return (string) $value;
    }
}