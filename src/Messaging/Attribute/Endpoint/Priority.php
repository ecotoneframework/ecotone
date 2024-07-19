<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\MessageHeaders;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
/**
 * licence Apache-2.0
 */
class Priority extends AddHeader
{
    public const DEFAULT_PRIORITY = 1;

    public function __construct(int $number = self::DEFAULT_PRIORITY)
    {
        parent::__construct(MessageHeaders::PRIORITY, $number);
    }
}
