<?php

namespace DoctrineRowHashBundle\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use DoctrineRowHashBundle\Attribute\RowHashColumn;
use DoctrineRowHashBundle\EventSubscriber\RowHashListener;
use DoctrineRowHashBundle\Message\RowHashMessage;
use DoctrineRowHashBundle\MessageHandler\RowHashHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class DoctrineRowHashIntegrationTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|Connection $connection;
    private MockObject|MessageBusInterface $messageBus;
    private MockObject|PropertyAccessor $propertyAccessor;
    private RowHashListener $rowHashListener;
    private RowHashHandler $rowHashHandler;

    protected function setUp(): void
    {
        // 设置模拟对象
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->propertyAccessor = $this->createMock(PropertyAccessor::class);

        // 创建监听器和处理器
        $this->rowHashListener = new RowHashListener(
            $this->propertyAccessor,
            $this->messageBus
        );

        $this->rowHashHandler = new RowHashHandler(
            $this->entityManager,
            $this->connection
        );
    }

    public function testIntegrationFlow(): void
    {
        // 创建测试实体
        $entity = new class() {
            #[RowHashColumn]
            private null $rowHash = null;

            public function getId(): string
            {
                return '123';
            }
            
            public function getRowHash(): null
            {
                return $this->rowHash;
            }
        };

        // 设置元数据模拟
        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->method('getName')->willReturn(get_class($entity));
        $metadata->method('getTableName')->willReturn('test_table');
        $metadata->method('getFieldNames')->willReturn(['id', 'rowHash']);

        $this->entityManager->method('getClassMetadata')
            ->with(get_class($entity))
            ->willReturn($metadata);

        // 设置消息总线行为 - 捕获发送的消息以便稍后使用
        $capturedMessage = null;
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (RowHashMessage $message) use (&$capturedMessage) {
                $capturedMessage = $message;
                return true;
            }))
            ->willReturn(new Envelope(new RowHashMessage()));

        // 设置预持久化事件参数
        $prePersistArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PrePersistEventArgs',
            [$entity, $this->entityManager]
        );

        // 设置属性访问器行为
        $this->propertyAccessor->expects($this->once())
            ->method('setValue')
            ->with($entity, 'rowHash', null);

        // 执行预持久化阶段
        $this->rowHashListener->prePersist($prePersistArgs);

        // 设置后持久化事件参数
        $postPersistArgs = $this->createEventArgs(
            'Doctrine\ORM\Event\PostPersistEventArgs',
            [$entity, $this->entityManager]
        );

        // 执行后持久化阶段 - 这将触发消息发送
        $this->rowHashListener->postPersist($postPersistArgs);

        // 确保消息已被捕获
        $this->assertNotNull($capturedMessage, '应该已经发送了消息');

        // 设置查询构建器模拟对象链
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        // 设置查询结果
        $entityData = [
            'id' => '123',
            'rowHash' => null,
        ];

        // 设置查询构建器行为
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->method('getResult')->willReturn([$entityData]);

        // 设置连接预期 - 应该执行哈希更新
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE test_table SET row_hash = :value1 WHERE id = :id',
                $this->callback(function ($params) {
                    return isset($params['value1']) && $params['id'] === '123';
                })
            );

        // 执行消息处理程序
        ($this->rowHashHandler)($capturedMessage);
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
