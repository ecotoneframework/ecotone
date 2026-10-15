<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\PollingMetadata;

/**
 * licence Apache-2.0
 */
class CalculatingConfiguration
{
    #[ServiceContext]
    public function registerMetadata()
    {
        return PollingMetadata::create('inboundCalculator')
            ->withHandledMessageLimit(1);
    }
}
