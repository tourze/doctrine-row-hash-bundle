# Doctrine Row Hash Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![License](https://img.shields.io/github/license/tourze/doctrine-row-hash-bundle.svg?style=flat-square)](LICENSE)

A Symfony Bundle that automatically calculates and stores row-level hash values in Doctrine entities. This is useful for detecting unauthorized data manipulation.

## Features

- Automatically calculate entity hash values and store them in designated fields
- Process hash calculations using asynchronous message queue without affecting main request performance
- Provide command-line tools to verify data integrity

## Installation

```bash
composer require tourze/doctrine-row-hash-bundle
```

## Configuration

Make sure the bundle is enabled in your Symfony project:

```php
# config/bundles.php
return [
    // ...
    DoctrineRowHashBundle\DoctrineRowHashBundle::class => ['all' => true],
    // ...
];
```

Ensure that message queue (Symfony Messenger) is configured to handle asynchronous messages.

## Quick Start

### Mark hash fields in entities

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

    // Add a field to store row hash value
    #[ORM\Column(nullable: true)]
    #[RowHashColumn]
    private ?string $rowHash = null;

    // getter and setter...
}
```

When the entity is created or updated, the system will automatically calculate the hash value and save it to the `rowHash` field.

### Verify data integrity

Use the command line tool to verify whether data has been tampered with:

```bash
bin/console app:row-hash "App\Entity\YourEntity" 123
```

Where `123` is the entity ID. If the data has been tampered with, the command will output a warning message.

## How It Works

1. Before the entity is persisted, `RowHashListener` sets the hash field to `null`
2. After the entity is persisted, the system sends an asynchronous message to the message queue
3. `RowHashHandler` processes these messages, calculates the hash value of the entity fields, and updates it to the database
4. Through the command line tool, you can recalculate the entity's hash value and compare it with the stored value to detect tampering

## Unit Testing

Run unit tests:

```bash
./vendor/bin/phpunit packages/doctrine-row-hash-bundle/tests
```

## Contributing

Issues and Pull Requests are welcome to improve this Bundle.

## License

This project is licensed under the MIT License.
