<?php

namespace DoctrineRowHashBundle\Tests\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use DoctrineRowHashBundle\Attribute\RowHashColumn;
use DoctrineRowHashBundle\EventSubscriber\RowHashListener;
use DoctrineRowHashBundle\Message\RowHashMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class RowHashListenerTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|MessageBusInterface $messageBus;
    private MockObject|PropertyAccessor $propertyAccessor;
    private RowHashListener $listener;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);

        $this->listener = new RowHashListener(
            $this->propertyAccessor,
            $this->messageBus
        );
    }

    public function testPrePersist(): void
    {
        // 创建一个带有RowHashColumn属性的测试实体
        $entity = new class() {
            #[RowHashColumn]
            private ?string $rowHash = null;

            public function getId(): string
            {
                return '123';
            }
        };

        // 模拟PropertyAccessor行为
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null);

        // 创建PrePersistEventArgs对象，因为它是final类不能mock
        $prePersistEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PrePersistEventArgs',
            [$entity, $this->entityManager]
        );

        $this->listener->prePersist($prePersistEventArgs);
    }

    public function testPostPersist(): void
    {
        // 创建一个带有RowHashColumn属性的测试实体
        $entity = new class() {
            #[RowHashColumn]
            private ?string $rowHash = null;

            public function getId(): string
            {
                return '123';
            }
        };

        // 设置元数据
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn(get_class($entity));
        $metadata->method('getTableName')->willReturn('test_table');
        $metadata->method('getFieldNames')->willReturn(['id', 'rowHash']);

        $this->entityManager->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);

        // 消息总线应该接收一个RowHashMessage消息
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RowHashMessage $message) use ($entity) {
                return $message->getClassName() === get_class($entity)
                    && $message->getId() === '123'
                    && $message->getTableName() === 'test_table';
            }))
            ->willReturn(new Envelope(new RowHashMessage()));

        // 创建PostPersistEventArgs对象，因为它是final类不能mock
        $postPersistEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PostPersistEventArgs',
            [$entity, $this->entityManager]
        );

        $this->listener->postPersist($postPersistEventArgs);
    }

    public function testPreUpdate(): void
    {
        // 创建一个带有RowHashColumn属性的测试实体
        $entity = new class() {
            #[RowHashColumn]
            private ?string $rowHash = null;

            public function getId(): string
            {
                return '123';
            }
        };

        // 创建PreUpdateEventArgs对象 - 这个可以模拟因为不是final类
        $changeSet = ['name' => ['old', 'new']];
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->method('getObject')->willReturn($entity);
        $args->method('getObjectManager')->willReturn($this->entityManager);
        $args->method('getEntityChangeSet')->willReturn($changeSet);

        // PropertyAccessor应被调用
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null);

        $this->listener->preUpdate($args);
    }

    public function testPostUpdate(): void
    {
        // 创建一个带有RowHashColumn属性的测试实体
        $entity = new class() {
            #[RowHashColumn]
            private ?string $rowHash = null;

            public function getId(): string
            {
                return '123';
            }
        };

        // 设置元数据
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getName')->willReturn(get_class($entity));
        $metadata->method('getTableName')->willReturn('test_table');
        $metadata->method('getFieldNames')->willReturn(['id', 'rowHash']);

        $this->entityManager->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);

        // 消息总线应该接收一个RowHashMessage消息
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RowHashMessage $message) use ($entity) {
                return $message->getClassName() === get_class($entity)
                    && $message->getId() === '123'
                    && $message->getTableName() === 'test_table';
            }))
            ->willReturn(new Envelope(new RowHashMessage()));

        // 创建PostUpdateEventArgs对象，因为它是final类不能mock
        $postUpdateEventArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PostUpdateEventArgs',
            [$entity, $this->entityManager]
        );

        $this->listener->postUpdate($postUpdateEventArgs);
    }

    /**
     * 创建一个final类的实例
     *
     * @param string $className 类名
     * @param array $constructorArgs 构造函数参数
     * @return object 创建的对象
     */
    private function createEventArgs(string $className, array $constructorArgs = []): object
    {
        $reflectionClass = new ReflectionClass($className);
        return $reflectionClass->newInstanceArgs($constructorArgs);
    }
}
