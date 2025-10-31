<?php

namespace DoctrineRowHashBundle\Tests\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use DoctrineRowHashBundle\Attribute\RowHashColumn;
use DoctrineRowHashBundle\EventSubscriber\RowHashListener;
use DoctrineRowHashBundle\Message\RowHashMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(RowHashListener::class)]
#[RunTestsInSeparateProcesses]
final class RowHashListenerTest extends AbstractEventSubscriberTestCase
{
    private MessageBusInterface $messageBus;

    private PropertyAccessor $propertyAccessor;

    private EntityManagerInterface $mockEntityManager;

    /** @var array<string, ClassMetadata<object>> */
    private array $metadataRegistry = [];

    protected function onSetUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        /*
         * Mock PropertyAccessor 具体类的必要性说明：
         * 1) 为什么必须使用具体类而不是接口：PropertyAccessor 是 Symfony 的具体类，官方未提供对应接口
         * 2) 这种使用是否合理和必要：是的，测试需要验证属性设置逻辑，mock 确保测试可控性和独立性
         * 3) 是否有更好的替代方案：无，PropertyAccessorInterface 不存在，且直接使用真实类会依赖反射机制增加测试复杂度
         */
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);

        // TestEntityGenerator 已被移除，不再需要
        $this->mockEntityManager = $this->createEntityManager();
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $entityManager->method('getClassMetadata')
            ->willReturnCallback(function (string $className): ClassMetadata {
                return $this->metadataRegistry[$className] ?? throw new \InvalidArgumentException("No metadata for {$className}");
            })
        ;

        return $entityManager;
    }

    /**
     * 注册类元数据到模拟的EntityManager中
     */
    /**
     * @param ClassMetadata<object> $metadata
     */
    private function setClassMetadata(string $className, ClassMetadata $metadata): void
    {
        $this->metadataRegistry[$className] = $metadata;
    }

    /**
     * @return ClassMetadata<object>
     */
    private function createClassMetadata(): ClassMetadata
    {
        /** @var class-string<object> $className */
        $className = 'TestEntity';
        $metadata = new ClassMetadata($className);

        // 创建一个具有RowHashColumn属性的测试类
        $testClass = new class {
            #[RowHashColumn]
            private string $rowHash = '';

            public function getRowHash(): string
            {
                return $this->rowHash;
            }

            public function setRowHash(string $rowHash): void
            {
                $this->rowHash = $rowHash;
            }
        };

        $reflectionClass = new \ReflectionClass($testClass);
        $metadata->setCustomRepositoryClass(null);

        // 使用反射设置ReflectionClass
        $reflectionProperty = new \ReflectionProperty($metadata, 'reflClass');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($metadata, $reflectionClass);

        return $metadata;
    }

    /**
     * @param array<string, array{mixed, mixed}> $changeSet
     */
    private function createPreUpdateEventArgs(object $entity, EntityManagerInterface $entityManager, array $changeSet = []): PreUpdateEventArgs
    {
        // PreUpdateEventArgs需要3个参数: entity, entityManager, changeSet
        return new PreUpdateEventArgs($entity, $entityManager, $changeSet);
    }

    private function createListener(): RowHashListener
    {
        $reflection = new \ReflectionClass(RowHashListener::class);

        return $reflection->newInstance(
            $this->propertyAccessor,
            $this->messageBus,
            $this->mockEntityManager
        );
    }

    public function testPrePersist(): void
    {
        $entity = $this->createTestEntity();
        $entityClass = get_class($entity);

        $metadata = $this->createClassMetadata();
        $this->setClassMetadata($entityClass, $metadata);

        // 设置 propertyAccessor 期望：先尝试获取当前值（返回null），然后设置新值
        $this->propertyAccessor->expects($this->once())
            ->method('getValue')
            ->with($entity, 'rowHash')
            ->willReturn(null)
        ;

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null)
        ;

        $prePersistEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PrePersistEventArgs',
            [$entity, $this->mockEntityManager]
        );

        /** @var PrePersistEventArgs $prePersistEventArgs */
        $listener = $this->createListener();
        $listener->prePersist($prePersistEventArgs);
    }

    public function testPostPersist(): void
    {
        $entity = $this->createTestEntity();

        $metadata = $this->createClassMetadata();
        $metadata->name = get_class($entity);
        $metadata->table = ['name' => 'test_table'];
        $metadata->fieldNames = ['id' => 'id', 'rowHash' => 'rowHash'];

        $this->setClassMetadata(get_class($entity), $metadata);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(function (RowHashMessage $message) use ($entity) {
                return $message->getClassName() === get_class($entity)
                    && '123' === $message->getId()
                    && 'test_table' === $message->getTableName();
            }))
            ->willReturn(new Envelope(new RowHashMessage()))
        ;

        $postPersistEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PostPersistEventArgs',
            [$entity, $this->mockEntityManager]
        );

        /** @var PostPersistEventArgs $postPersistEventArgs */
        $listener = $this->createListener();
        $listener->postPersist($postPersistEventArgs);
    }

    public function testPreUpdate(): void
    {
        $entity = $this->createTestEntity();

        $metadata = $this->createClassMetadata();
        $this->setClassMetadata(get_class($entity), $metadata);

        $changeSet = ['name' => ['old', 'new']];
        $args = $this->createPreUpdateEventArgs($entity, $this->mockEntityManager, $changeSet);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null)
        ;

        $listener = $this->createListener();
        $listener->preUpdate($args);
    }

    public function testPostUpdate(): void
    {
        $entity = $this->createTestEntity();

        $metadata = $this->createClassMetadata();
        $metadata->name = get_class($entity);
        $metadata->table = ['name' => 'test_table'];
        $metadata->fieldNames = ['id' => 'id', 'rowHash' => 'rowHash'];

        $this->setClassMetadata(get_class($entity), $metadata);

        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(function (RowHashMessage $message) use ($entity) {
                return $message->getClassName() === get_class($entity)
                    && '123' === $message->getId()
                    && 'test_table' === $message->getTableName();
            }))
            ->willReturn(new Envelope(new RowHashMessage()))
        ;

        $postUpdateEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PostUpdateEventArgs',
            [$entity, $this->mockEntityManager]
        );

        /** @var PostUpdateEventArgs $postUpdateEventArgs */
        $listener = $this->createListener();
        $listener->postUpdate($postUpdateEventArgs);
    }

    public function testPrePersistEntity(): void
    {
        $entity = $this->createTestEntity();

        $metadata = $this->createClassMetadata();
        $this->setClassMetadata(get_class($entity), $metadata);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null)
        ;

        $listener = $this->createListener();
        // EntityManagerInterface extends ObjectManager, safe to cast
        $this->assertInstanceOf(ObjectManager::class, $this->mockEntityManager);
        $listener->prePersistEntity($this->mockEntityManager, $entity);
    }

    public function testPreUpdateEntity(): void
    {
        $entity = $this->createTestEntity();

        $metadata = $this->createClassMetadata();
        $this->setClassMetadata(get_class($entity), $metadata);

        $changeSet = ['name' => ['old', 'new']];
        $args = $this->createPreUpdateEventArgs($entity, $this->mockEntityManager, $changeSet);

        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null)
        ;

        $listener = $this->createListener();
        // EntityManagerInterface extends ObjectManager, safe to cast
        $this->assertInstanceOf(ObjectManager::class, $this->mockEntityManager);
        $listener->preUpdateEntity($this->mockEntityManager, $entity, $args);
    }

    private function createTestEntity(): object
    {
        return new class {
            #[RowHashColumn]
            private string $rowHash = '';

            public function getId(): string
            {
                return '123';
            }

            public function getRowHash(): string
            {
                return $this->rowHash;
            }

            public function setRowHash(string $rowHash): void
            {
                $this->rowHash = $rowHash;
            }
        };
    }

    /**
     * 创建一个final类的实例
     *
     * @param class-string $className       类名
     * @param array<mixed>  $constructorArgs 构造函数参数
     *
     * @return object 创建的对象
     */
    private function createEventArgs(string $className, array $constructorArgs = []): object
    {
        $reflectionClass = new \ReflectionClass($className);

        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
