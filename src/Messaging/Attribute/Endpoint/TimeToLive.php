<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TimeSpan;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class TimeToLive extends AddHeader
{
    /**
     * @param int|TimeSpan $time if integer is provided it is treated as milliseconds
     */
    public function __construct(int|TimeSpan|null $time = null, ?string $expression = null)
    {
        parent::__construct(MessageHeaders::TIME_TO_LIVE, $time instanceof TimeSpan ? $time->toMilliseconds() : $time, $expression);
    }
}
