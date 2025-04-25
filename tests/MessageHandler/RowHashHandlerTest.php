<?php

namespace DoctrineRowHashBundle\Tests\MessageHandler;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use DoctrineRowHashBundle\Message\RowHashMessage;
use DoctrineRowHashBundle\MessageHandler\RowHashHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RowHashHandlerTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|Connection $connection;
    private RowHashHandler $handler;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->handler = new RowHashHandler(
            $this->entityManager,
            $this->connection
        );
    }

    public function testInvoke(): void
    {
        // 创建RowHashMessage
        $message = new RowHashMessage();
        $message->setClassName('App\Entity\TestEntity');
        $message->setTableName('test_table');
        $message->setId('123');
        $message->setColumnNames(['id', 'name', 'description', 'row_hash']);

        // 模拟查询构建器链
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        // 模拟查询结果
        $entityData = [
            'id' => '123',
            'name' => '测试实体',
            'description' => '这是一个测试实体',
            'row_hash' => null,
        ];

        // 设置查询构建器期望
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('e.id, e.name, e.description, e.row_hash')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->with('App\Entity\TestEntity', 'e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id = :id')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('id', '123')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn([$entityData]);

        // 验证SQL执行
        // 计算哈希值 - 我们不能直接测试内部实现，但可以确保方法被调用
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                'UPDATE test_table SET row_hash = :value1 WHERE id = :id',
                $this->callback(function ($params) {
                    // 验证参数包含哈希值和ID
                    return isset($params['value1']) && $params['id'] === '123';
                })
            );

        // 执行处理器
        ($this->handler)($message);
    }
}
