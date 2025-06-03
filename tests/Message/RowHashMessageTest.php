<?php

namespace DoctrineRowHashBundle\Tests\Message;

use DoctrineRowHashBundle\Message\RowHashMessage;
use PHPUnit\Framework\TestCase;
use Tourze\AsyncContracts\AsyncMessageInterface;

class RowHashMessageTest extends TestCase
{
    public function testMessageImplementsInterface(): void
    {
        $message = new RowHashMessage();
        $this->assertInstanceOf(AsyncMessageInterface::class, $message);
    }

    public function testSetAndGetColumnNames(): void
    {
        $message = new RowHashMessage();
        $columnNames = ['id', 'name', 'description', 'row_hash'];

        $message->setColumnNames($columnNames);
        $this->assertEquals($columnNames, $message->getColumnNames());
    }

    public function testSetAndGetClassName(): void
    {
        $message = new RowHashMessage();
        $className = 'App\Entity\TestEntity';

        $message->setClassName($className);
        $this->assertEquals($className, $message->getClassName());
    }

    public function testSetAndGetTableName(): void
    {
        $message = new RowHashMessage();
        $tableName = 'test_table';

        $message->setTableName($tableName);
        $this->assertEquals($tableName, $message->getTableName());
    }

    public function testSetAndGetId(): void
    {
        $message = new RowHashMessage();
        $id = '123';

        $message->setId($id);
        $this->assertEquals($id, $message->getId());
    }
}
