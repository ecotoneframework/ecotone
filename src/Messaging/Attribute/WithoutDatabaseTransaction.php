<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
#[Attribute]
class WithoutDatabaseTransaction implements AsynchronousEndpointAttribute, DefinedObject
{
    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
