<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\Messaging\Annotation\Endpoint\Delayed;
use Ecotone\Messaging\Annotation\Endpoint\Priority;
use Ecotone\Messaging\Annotation\Endpoint\ExpireAfter;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class EndpointHeadersInterceptor
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EndpointHeadersInterceptor
{
    public function addMetadata(?Delayed $deliveryDelay, ?ExpireAfter $timeToLive, ?Priority $priority) : array
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