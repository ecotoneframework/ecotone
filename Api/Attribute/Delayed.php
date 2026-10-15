<?php

declare(strict_types=1);

namespace Ecotone\Api\Attribute;

use Attribute;
use Closure;
use DateTimeInterface;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Api\Scheduling\TimeSpan;
use Ecotone\Messaging\Support\Assert;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Delayed extends AddHeader
{
    /**
     * @param int|TimeSpan|DateTimeInterface $time if integer is provided it is treated as milliseconds
     */
    public function __construct(
        int|TimeSpan|DateTimeInterface|null $time = null,
        string|Closure|null $expression = null,
        private readonly bool $shouldReplaceExistingHeader = true,
        int $milliseconds = 0,
        int $seconds = 0,
        int $minutes = 0,
        int $hours = 0,
        int $days = 0,
    ) {
        $namedDuration = new TimeSpan($milliseconds, $seconds, $minutes, $hours, $days);
        if ($namedDuration->toMilliseconds() > 0) {
            Assert::isTrue($time === null && $expression === null, '#[Delayed] takes either $time or named durations (milliseconds, seconds, minutes, hours, days), not both.');
            $time = $namedDuration;
        }

        parent::__construct(MessageHeaders::DELIVERY_DELAY, $time, $expression);
    }

    public function shouldReplaceExistingHeader(): bool
    {
        return $this->shouldReplaceExistingHeader;
    }
}
