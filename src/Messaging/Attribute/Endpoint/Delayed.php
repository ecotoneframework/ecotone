<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use DateTimeInterface;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\TimeSpan;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Delayed extends AddHeader
{
    /**
     * @param int|TimeSpan|DateTimeInterface $time if integer is provided it is treated as milliseconds
     */
    public function __construct(int|TimeSpan|DateTimeInterface|null $time = null, ?string $expression = null)
    {
        parent::__construct(MessageHeaders::DELIVERY_DELAY, $time, $expression);
    }
}
