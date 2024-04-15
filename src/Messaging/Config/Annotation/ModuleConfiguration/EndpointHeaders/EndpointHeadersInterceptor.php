<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration\EndpointHeaders;

use Ecotone\Messaging\Attribute\Endpoint\AddHeader;
use Ecotone\Messaging\Attribute\Endpoint\Delayed;
use Ecotone\Messaging\Attribute\Endpoint\Priority;
use Ecotone\Messaging\Attribute\Endpoint\RemoveHeader;
use Ecotone\Messaging\Attribute\Endpoint\TimeToLive;
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
    public function addMetadata(?AddHeader $addHeader, ?Delayed $delayed, ?Priority $priority, ?TimeToLive $timeToLive, ?RemoveHeader $removeHeader): array
    {
        $metadata = [];

        if ($addHeader) {
            $metadata[$addHeader->getHeaderName()] = $addHeader->getHeaderValue();
        }

        if ($delayed) {
            $metadata[MessageHeaders::DELIVERY_DELAY] = $delayed->getHeaderValue();
        }

        if ($priority) {
            $metadata[MessageHeaders::PRIORITY] = $priority->getHeaderValue();
        }

        if ($timeToLive) {
            $metadata[MessageHeaders::TIME_TO_LIVE] = $timeToLive->getHeaderValue();
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
