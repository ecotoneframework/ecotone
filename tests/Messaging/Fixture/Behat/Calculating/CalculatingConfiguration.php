<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;

class CalculatingConfiguration
{
    #[ServiceContext]
    public function registerMetadata()
    {
        return PollingMetadata::create("inboundCalculator")
            ->setHandledMessageLimit(1);
    }
}