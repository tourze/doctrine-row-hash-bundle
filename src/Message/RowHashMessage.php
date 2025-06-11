<?php

namespace DoctrineRowHashBundle\Message;

use Tourze\AsyncContracts\AsyncMessageInterface;

class RowHashMessage implements AsyncMessageInterface
{
    private array $columnNames;

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    public function setColumnNames($columnNames): void
    {
        $this->columnNames = $columnNames;
    }

    private string $className;

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    private string $tableName;

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): void
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
