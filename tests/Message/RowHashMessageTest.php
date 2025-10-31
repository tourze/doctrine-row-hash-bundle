<?php

namespace DoctrineRowHashBundle\Tests\Message;

use DoctrineRowHashBundle\Message\RowHashMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AsyncContracts\AsyncMessageInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RowHashMessage::class)]
#[RunTestsInSeparateProcesses]
final class RowHashMessageTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 消息类测试不需要特殊的设置
    }

    public function testMessageImplementsInterface(): void
    {
        // @phpstan-ignore-next-line 数据传输对象测试需要直接实例化来验证接口实现
        $message = new RowHashMessage();
        $this->assertInstanceOf(AsyncMessageInterface::class, $message);
    }

    public function testSetAndGetColumnNames(): void
    {
        // @phpstan-ignore-next-line 数据传输对象测试需要直接实例化来验证属性访问
        $message = new RowHashMessage();
        $columnNames = ['id', 'name', 'description', 'row_hash'];

        $message->setColumnNames($columnNames);
        $this->assertEquals($columnNames, $message->getColumnNames());
    }

    public function testSetAndGetClassName(): void
    {
        // @phpstan-ignore-next-line 数据传输对象测试需要直接实例化来验证属性访问
        $message = new RowHashMessage();
        $className = 'App\Entity\TestEntity';

        $message->setClassName($className);
        $this->assertEquals($className, $message->getClassName());
    }

    public function testSetAndGetTableName(): void
    {
        // @phpstan-ignore-next-line 数据传输对象测试需要直接实例化来验证属性访问
        $message = new RowHashMessage();
        $tableName = 'test_table';

        $message->setTableName($tableName);
        $this->assertEquals($tableName, $message->getTableName());
    }

    public function testSetAndGetId(): void
    {
        // @phpstan-ignore-next-line 数据传输对象测试需要直接实例化来验证属性访问
        $message = new RowHashMessage();
        $id = '123';

        $message->setId($id);
        $this->assertEquals($id, $message->getId());
    }
}
