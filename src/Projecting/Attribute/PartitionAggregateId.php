<?php

/*
 * licence Apache-2.0
 */
declare(strict_types=1);

namespace Ecotone\Projecting\Attribute;

use Attribute;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\MessageHeaders;

#[Attribute(Attribute::TARGET_PARAMETER)]
class PartitionAggregateId extends Header
{
    public function __construct()
    {
    }

    public function getHeaderName(): string
    {
        return MessageHeaders::EVENT_AGGREGATE_ID;
    }
}
