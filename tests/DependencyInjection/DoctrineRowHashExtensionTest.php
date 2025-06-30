<?php

namespace DoctrineRowHashBundle\Tests\DependencyInjection;

use DoctrineRowHashBundle\DependencyInjection\DoctrineRowHashExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineRowHashExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new DoctrineRowHashExtension();
        
        $extension->load([], $container);
        
        // 验证服务是否被正确加载
        $this->assertTrue($container->has('DoctrineRowHashBundle\EventSubscriber\RowHashListener'));
        $this->assertTrue($container->has('DoctrineRowHashBundle\Command\VerifyRowHashCommand'));
        $this->assertTrue($container->has('DoctrineRowHashBundle\MessageHandler\RowHashHandler'));
    }
}