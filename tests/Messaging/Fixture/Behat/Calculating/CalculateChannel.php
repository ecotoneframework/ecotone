<?php

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

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
