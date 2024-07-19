<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;

/**
 * licence Apache-2.0
 */
class CalculateChannel
{
    #[ServiceContext]
    public function configuration(): array
    {
        return [
            SimpleMessageChannelBuilder::createQueueChannel('resultChannel', conversionMediaType: MediaType::createApplicationXPHP()),
        ];
    }
}
