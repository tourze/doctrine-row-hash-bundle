<?php

declare(strict_types=1);

namespace DoctrineRowHashBundle\Attribute;

/**
 * 记录创建时哈希值
 */
#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class RowHashColumn
{
}
