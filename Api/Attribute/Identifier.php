<?php

namespace Ecotone\Api\Attribute;

use Attribute;
use Ecotone\Modelling\AggregateMessage;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
class Identifier extends Header
{
    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER;
    }
}
