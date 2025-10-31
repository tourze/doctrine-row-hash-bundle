<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use DoctrineRowHashBundle\Attribute\RowHashColumn;
use DoctrineRowHashBundle\Exception\EntityMissingIdMethodException;
use DoctrineRowHashBundle\Message\RowHashMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\DoctrineEntityCheckerBundle\Checker\EntityCheckerInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: -99)]
#[AsDoctrineListener(event: Events::preUpdate, priority: -99)]
#[AsDoctrineListener(event: Events::postPersist, priority: -99)]
#[AsDoctrineListener(event: Events::postUpdate, priority: -99)]
readonly class RowHashListener implements EntityCheckerInterface
{
    public function __construct(
        #[Autowire(service: 'doctrine-row-hash.property-accessor')] private PropertyAccessor $propertyAccessor,
        private MessageBusInterface $messageBus,
        private EntityManagerInterface $entityManager,
    ) {
    }

    private function entityHasHashColumn(string $className): bool
    {
        foreach ($this->entityManager->getClassMetadata($className)->getReflectionClass()->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if (count($property->getAttributes(RowHashColumn::class)) > 0) {
                return true;
            }
        }

        return false;
    }

    /** @param ClassMetadata<object> $metadata */
    private function createRowHashMessage(ClassMetadata $metadata, string $id): void
    {
        $className = $metadata->getName();
        if (!$this->entityHasHashColumn($className)) {
            return;
        }
        $rowHashMessage = new RowHashMessage();
        $rowHashMessage->setColumnNames($metadata->getFieldNames());
        $rowHashMessage->setTableName($metadata->getTableName());
        $rowHashMessage->setClassName($className);
        $rowHashMessage->setId($id);
        $this->messageBus->dispatch($rowHashMessage);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $metadata = $args->getObjectManager()->getClassMetadata(get_class($args->getObject()));
        $object = $args->getObject();
        if (!method_exists($object, 'getId')) {
            throw new EntityMissingIdMethodException(get_class($object));
        }
        $id = $object->getId();
        if (!is_string($id) && !is_int($id)) {
            throw new \InvalidArgumentException('Entity ID must be string or integer');
        }
        $this->createRowHashMessage($metadata, (string) $id);
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $metadata = $args->getObjectManager()->getClassMetadata(get_class($args->getObject()));
        $object = $args->getObject();
        if (!method_exists($object, 'getId')) {
            throw new EntityMissingIdMethodException(get_class($object));
        }
        $id = $object->getId();
        if (!is_string($id) && !is_int($id)) {
            throw new \InvalidArgumentException('Entity ID must be string or integer');
        }
        $this->createRowHashMessage($metadata, (string) $id);
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $this->prePersistEntity($args->getObjectManager(), $args->getObject());
    }

    public function prePersistEntity(ObjectManager $objectManager, object $entity): void
    {
        foreach ($this->entityManager->getClassMetadata(get_class($entity))->getReflectionClass()->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if (0 === count($property->getAttributes(RowHashColumn::class))) {
                continue;
            }

            try {
                $oldValue = $this->propertyAccessor->getValue($entity, $property->getName());
                if (null !== $oldValue) {
                    continue;
                }
            } catch (UninitializedPropertyException $exception) {
                // The property "XXX\Entity\XXX::$createTime" is not readable because it is typed "DateTimeInterface". You should initialize it or declare a default value instead.
                // 跳过这个错误
            }
            $this->propertyAccessor->setValue($entity, $property->getName(), null);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        // 如果数据都没变化，那我们也没必要更新时间
        if (0 === count($args->getEntityChangeSet())) {
            return;
        }
        $this->preUpdateEntity($args->getObjectManager(), $args->getObject(), $args);
    }

    public function preUpdateEntity(ObjectManager $objectManager, object $entity, PreUpdateEventArgs $eventArgs): void
    {
        foreach ($this->entityManager->getClassMetadata(get_class($entity))->getReflectionClass()->getProperties(\ReflectionProperty::IS_PRIVATE) as $property) {
            if (0 === count($property->getAttributes(RowHashColumn::class))) {
                continue;
            }
            $this->propertyAccessor->setValue($entity, $property->getName(), null);
        }
    }
}
