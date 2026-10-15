<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ErrorChannel implements AsynchronousEndpointAttribute, DefinedObject
{
    public function __construct(
        public readonly string   $errorChannelName,
    ) {
        Assert::notNullAndEmpty($errorChannelName, 'Channel name can not be empty string');
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class, [$this->errorChannelName]);
    }
}
