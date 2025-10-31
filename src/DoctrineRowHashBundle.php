<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;

class DoctrineRowHashBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            DoctrineEntityCheckerBundle::class => ['all' => true],
        ];
    }
}
