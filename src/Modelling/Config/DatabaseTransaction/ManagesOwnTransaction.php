<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Config\DatabaseTransaction;

use Attribute;
use Ecotone\Api\Attribute\AsynchronousEndpointAttribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;

/**
 * licence Apache-2.0
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class ManagesOwnTransaction implements AsynchronousEndpointAttribute, DefinedObject
{
    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
