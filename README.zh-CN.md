# Doctrine Row Hash Bundle

[English](README.md) | 中文

这个Symfony Bundle提供了一种机制，用于在Doctrine实体中自动计算并存储行级别的哈希值。这对于检测数据是否被篡改非常有用。

## 功能特性

- 自动计算实体的哈希值并存储到指定的字段中
- 使用异步消息队列处理哈希计算，不影响主请求性能
- 提供命令行工具来验证数据是否被篡改

## 安装说明

```bash
composer require tourze/doctrine-row-hash-bundle
```

## 配置

在您的Symfony项目中，确保已启用该Bundle：

```php
# config/bundles.php
return [
    // ...
    DoctrineRowHashBundle\DoctrineRowHashBundle::class => ['all' => true],
    // ...
];
```

确保配置了消息队列（Symfony Messenger）以处理异步消息。

## 快速开始

### 在实体中标记哈希字段

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DoctrineRowHashBundle\Attribute\RowHashColumn;

#[ORM\Entity]
class YourEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    // 添加一个字段用于存储行哈希值
    #[ORM\Column(nullable: true)]
    #[RowHashColumn]
    private ?string $rowHash = null;

    // getter and setter...
}
```

当实体被创建或更新时，系统会自动计算哈希值并保存到 `rowHash` 字段中。

### 验证数据完整性

使用命令行工具来验证数据是否被篡改：

```bash
bin/console app:row-hash "App\Entity\YourEntity" 123
```

其中，`123` 是实体的ID。如果数据被篡改，命令将输出警告信息。

## 工作原理

1. 当实体被持久化前，`RowHashListener` 会将哈希字段设置为 `null`
2. 当实体被持久化后，系统会发送一个异步消息到消息队列
3. `RowHashHandler` 处理这些消息，计算实体字段的哈希值，并更新到数据库中
4. 通过命令行工具，可以重新计算实体的哈希值，并与存储的值比较，检测是否被篡改

## 单元测试

运行单元测试：

```bash
./vendor/bin/phpunit packages/doctrine-row-hash-bundle/tests
```

## 贡献指南

欢迎提交Issue和Pull Request来改进这个Bundle。

## 许可证

本项目采用MIT许可证。 