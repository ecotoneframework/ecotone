<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\Calculating;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * @ApplicationContext()
 */
class CalculatingConfiguration
{
    /**
     * @Extension()
     */
    public function registerMetadata()
    {
        return PollingMetadata::create("inboundCalculator")
                    ->setHandledMessageLimit(1);
    }
}