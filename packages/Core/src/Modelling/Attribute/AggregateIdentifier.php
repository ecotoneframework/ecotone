<?php

namespace Ecotone\Modelling\Attribute;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\AggregateMessage;

#[\Attribute(\Attribute::TARGET_PROPERTY|\Attribute::TARGET_PARAMETER)]
class AggregateIdentifier extends Header
{
    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER;
    }
}