<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Attribute\Endpoint;

use Attribute;
use Ecotone\Messaging\MessageHeaders;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Priority extends AddHeader
{
    public function __construct(int $number)
    {
        parent::__construct(MessageHeaders::PRIORITY, $number);
    }
}
