<?php

namespace DoctrineRowHashBundle\Tests\Unit;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use DoctrineRowHashBundle\DoctrineRowHashBundle;
use PHPUnit\Framework\TestCase;
use Tourze\DoctrineEntityCheckerBundle\DoctrineEntityCheckerBundle;

class DoctrineRowHashBundleTest extends TestCase
{
    public function testGetBundleDependencies(): void
    {
        $dependencies = DoctrineRowHashBundle::getBundleDependencies();
        
        $this->assertArrayHasKey(DoctrineBundle::class, $dependencies);
        $this->assertArrayHasKey(DoctrineEntityCheckerBundle::class, $dependencies);
        
        $this->assertEquals(['all' => true], $dependencies[DoctrineBundle::class]);
        $this->assertEquals(['all' => true], $dependencies[DoctrineEntityCheckerBundle::class]);
    }
    
    public function testBundleImplementsBundleDependencyInterface(): void
    {
        $bundle = new DoctrineRowHashBundle();
        $this->assertInstanceOf('Tourze\BundleDependency\BundleDependencyInterface', $bundle);
    }
    
    public function testBundleExtendsSymfonyBundle(): void
    {
        $bundle = new DoctrineRowHashBundle();
        $this->assertInstanceOf('Symfony\Component\HttpKernel\Bundle\Bundle', $bundle);
    }
}