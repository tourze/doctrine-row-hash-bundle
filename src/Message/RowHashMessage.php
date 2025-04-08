<?php

namespace DoctrineRowHashBundle\Message;

use Tourze\Symfony\Async\Message\AsyncMessageInterface;

class RowHashMessage implements AsyncMessageInterface
{
    /**
     * @var array
     */
    private $columnNames;

    /**
     * @return array
     */
    public function getColumnNames()
    {
        return $this->columnNames;
    }

    public function setColumnNames($columnNames): void
    {
        $this->columnNames = $columnNames;
    }

    /**
     * @var string
     */
    private $className;

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName($className): void
    {
        $this->className = $className;
    }

    /**
     * @var string
     */
    private $tableName;

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @var string id
     */
    private string $id;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
