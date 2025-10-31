<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle\Tests;

use DoctrineRowHashBundle\DoctrineRowHashBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(DoctrineRowHashBundle::class)]
#[RunTestsInSeparateProcesses]
final class DoctrineRowHashBundleTest extends AbstractBundleTestCase
{
}
