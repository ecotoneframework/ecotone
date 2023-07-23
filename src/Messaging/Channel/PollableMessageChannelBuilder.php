<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel;

use Ecotone\Messaging\Conversion\MediaType;

interface PollableMessageChannelBuilder extends MessageChannelBuilder
{
    public function getDefaultConversionMediaType(): ?MediaType;
}
