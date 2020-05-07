<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\Messaging\Annotation\Endpoint\WithDelay;
use Ecotone\Messaging\Annotation\Endpoint\WithPriority;
use Ecotone\Messaging\Annotation\Endpoint\WithTimeToLive;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class EndpointHeadersInterceptor
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EndpointHeadersInterceptor
{
    public function addMetadata(?WithDelay $deliveryDelay, ?WithTimeToLive $timeToLive, ?WithPriority $priority) : array
    {
        $metadata = [];

        if ($deliveryDelay) {
            $metadata[MessageHeaders::DELIVERY_DELAY] = $deliveryDelay->getTime();
        }

        if ($timeToLive) {
            $metadata[MessageHeaders::TIME_TO_LIVE] = $timeToLive->getTime();
        }

        if ($priority) {
            $metadata[MessageHeaders::PRIORITY] = $priority->getNumber();
        }

        return $metadata;
    }
}