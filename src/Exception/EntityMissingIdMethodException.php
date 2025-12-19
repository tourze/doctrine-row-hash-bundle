<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle\Exception;

final class EntityMissingIdMethodException extends \InvalidArgumentException
{
    public function __construct(string $entityClass)
    {
        parent::__construct(sprintf('Entity "%s" must have getId() method for row hash functionality', $entityClass));
    }
}
