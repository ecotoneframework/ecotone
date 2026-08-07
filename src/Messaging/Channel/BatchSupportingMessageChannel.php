<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

/**
 * licence Apache-2.0
 */
interface BatchSupportingMessageChannel
{
    public function supportsBatchMessages(): bool;
}
