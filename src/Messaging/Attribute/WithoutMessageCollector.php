<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Enterprise
 */
#[Attribute]
class WithoutMessageCollector implements AsynchronousEndpointAttribute, DefinedObject
{
    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
