<?php

namespace DoctrineRowHashBundle\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use DoctrineRowHashBundle\Command\VerifyRowHashCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class VerifyRowHashCommandTest extends TestCase
{
    private MockObject|EntityManagerInterface $entityManager;
    private VerifyRowHashCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->command = new VerifyRowHashCommand($this->entityManager);

        $application = new Application();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecute(): void
    {
        // 测试类名和ID
        $className = 'App\Entity\TestEntity';
        $id = '123';

        // 设置元数据
        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->method('getFieldNames')->willReturn(['id', 'name', 'rowHash']);

        $this->entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->willReturn($metadata);

        // 模拟查询构建器
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->getMock();

        // 模拟查询结果 - 当前哈希与计算哈希不匹配，表示数据被篡改
        $queryResult = [
            [
                'id' => '123',
                'name' => '测试实体',
                'rowHash' => 'original_hash_value',
            ]
        ];

        // 设置查询构建器链预期
        $this->entityManager->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('e.id, e.name, e.rowHash')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('from')
            ->with($className, 'e')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('where')
            ->with('e.id = :id')
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('setParameter')
            ->with('id', $id)
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($queryResult);

        // 执行命令
        $this->commandTester->execute([
            'className' => $className,
            'id' => $id,
        ]);

        // 验证输出包含篡改警告
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('数据经过篡改', $output);
    }
}
