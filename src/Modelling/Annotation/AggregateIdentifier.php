<?php

namespace Ecotone\Modelling\Annotation;

use Ecotone\Messaging\Support\Assert;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AggregateIdentifier
{
    /**
     * Name of the routing key property on messages that provides the identifier
     */
    public string $targetIdentifierName;

    public function __construct(string $targetIdentifierName = "")
    {
        $this->targetIdentifierName = $targetIdentifierName;
    }

    public function getTargetIdentifierName(): string
    {
        return $this->targetIdentifierName;
    }
}