<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\Collector;

use Ecotone\Api\Messaging\Message;

/**
 * licence Apache-2.0
 */
final class CollectedMessages
{
    /**
     * @param Message[] $messages
     */
    public function __construct(public bool $isCollecting, public array $messages)
    {
    }
}
