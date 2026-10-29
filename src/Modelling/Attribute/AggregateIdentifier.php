<?php

namespace Ecotone\Modelling\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Modelling\AggregateMessage;

/**
 * @deprecated Ecotone 2.0 will drop this attribute. Use #[Identifier] instead
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
/**
 * licence Apache-2.0
 */
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
