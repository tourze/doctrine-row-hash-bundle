<?php

namespace DoctrineRowHashBundle\Tests\Attribute;

use DoctrineRowHashBundle\Attribute\RowHashColumn;
use PHPUnit\Framework\TestCase;

class RowHashColumnTest extends TestCase
{
    public function testAttributeCanBeApplied(): void
    {
        // 定义一个测试类
        $testClass = new class() {
            #[RowHashColumn]
            private null $rowHash = null;
            
            public function getRowHash(): null
            {
                return $this->rowHash;
            }
        };

        // 获取该类的反射并检查第一个属性是否具有RowHashColumn属性
        $reflection = new \ReflectionClass($testClass);
        $property = $reflection->getProperty('rowHash');
        $attributes = $property->getAttributes(RowHashColumn::class);

        $this->assertCount(1, $attributes, '属性应该有一个RowHashColumn标记');
    }

    public function testAttributeTargetProperty(): void
    {
        // 验证属性只能应用于类属性
        $reflection = new \ReflectionClass(RowHashColumn::class);
        $attributes = $reflection->getAttributes();

        $this->assertCount(1, $attributes, 'RowHashColumn应该有一个属性标记');
        $this->assertEquals(\Attribute::class, $attributes[0]->getName());

        $attributeInstance = $attributes[0]->newInstance();
        $this->assertEquals(\Attribute::TARGET_PROPERTY, $attributeInstance->flags);
    }
}
