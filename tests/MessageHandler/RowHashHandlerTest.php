<?php

namespace DoctrineRowHashBundle\Tests\MessageHandler;

use Doctrine\Persistence\Mapping\MappingException;
use DoctrineRowHashBundle\Message\RowHashMessage;
use DoctrineRowHashBundle\MessageHandler\RowHashHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(RowHashHandler::class)]
#[RunTestsInSeparateProcesses]
final class RowHashHandlerTest extends AbstractIntegrationTestCase
{
    private RowHashHandler $handler;

    protected function onSetUp(): void
    {
        $this->handler = self::getService(RowHashHandler::class);
    }

    public function testInvoke(): void
    {
        // 创建RowHashMessage
        $message = new RowHashMessage();
        $message->setClassName('App\Entity\TestEntity');
        $message->setTableName('test_table');
        $message->setId('123');
        $message->setColumnNames(['id', 'name', 'description', 'row_hash']);

        // 执行处理器（集成测试：使用真实的依赖进行测试）
        // 由于使用的是不存在的实体类，应该抛出MappingException
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("Class 'App\\Entity\\TestEntity' does not exist");

        ($this->handler)($message);
    }
}
