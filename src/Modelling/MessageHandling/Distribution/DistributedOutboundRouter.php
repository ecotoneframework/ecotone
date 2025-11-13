<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution;

use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Api\Distribution\DistributedBusHeader;
use Ecotone\Modelling\Api\Distribution\DistributedServiceMap;

/**
 * licence Enterprise
 */
final class DistributedOutboundRouter
{
    public function __construct(
        private DistributedServiceMap $distributedServiceMap,
        private string $thisServiceName
    ) {

    }

    public function route(
        #[Header(DistributedBusHeader::DISTRIBUTED_PAYLOAD_TYPE)]
        string  $payloadType,
        #[Header(DistributedBusHeader::DISTRIBUTED_TARGET_SERVICE_NAME)]
        ?string $targetedServiceName,
        #[Header(DistributedBusHeader::DISTRIBUTED_ROUTING_KEY)]
        $routingKey,
    ): array {
        if ($payloadType === 'event') {
            return $this->distributedServiceMap->getAllChannelNamesBesides($this->thisServiceName, $routingKey);
        } elseif (in_array($payloadType, ['command', 'message'])) {
            Assert::isTrue($targetedServiceName !== null, sprintf('
                Cannot send commands to shared channel - `%s`. Commands follow point-to-point semantics, and shared channels are reserved for events only.
                Change your channel to standard pollable channel.
            ', $targetedServiceName));

            return [$this->distributedServiceMap->getChannelNameFor($targetedServiceName)];
        } else {
            throw InvalidArgumentException::create("Trying to call distributed command handler for payload type {$payloadType} and allowed are event/command/message");
        }
    }
}
