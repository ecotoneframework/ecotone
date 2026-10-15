<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
#[Attribute]
class WithoutMessageCollector implements AsynchronousEndpointAttribute, DefinedObject
{
    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
