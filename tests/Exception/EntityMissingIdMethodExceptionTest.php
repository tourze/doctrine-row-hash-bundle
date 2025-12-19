<?php

namespace DoctrineRowHashBundle\Tests\Exception;

use DoctrineRowHashBundle\Exception\EntityMissingIdMethodException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(EntityMissingIdMethodException::class)]
final class EntityMissingIdMethodExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionMessage(): void
    {
        $entityClass = 'App\Entity\TestEntity';
        $exception = new EntityMissingIdMethodException($entityClass);

        $this->assertStringContainsString($entityClass, $exception->getMessage());
        $this->assertStringContainsString('must have getId() method', $exception->getMessage());
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}
