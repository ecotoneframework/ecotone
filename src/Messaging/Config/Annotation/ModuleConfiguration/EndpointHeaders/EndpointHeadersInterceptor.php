<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\ExpireAfter;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\MessageHeaders;

/**
 * Class EndpointHeadersInterceptor
 * @package Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class EndpointHeadersInterceptor implements DefinedObject
{
    public function addMetadata(?Delayed $deliveryDelay, ?ExpireAfter $timeToLive, ?Priority $priority, ?AddHeader $addHeader, ?RemoveHeader $removeHeader): array
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

        if ($addHeader) {
            $metadata[$addHeader->getHeaderName()] = $addHeader->getHeaderValue();
        }

        if ($removeHeader) {
            $metadata[$removeHeader->getHeaderName()] = null;
        }

        return $metadata;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
