<?php

declare(strict_types=1);

namespace Ecotone\Modelling\MessageHandling\Distribution;

use Ecotone\Api\Attribute\Header;
use Ecotone\Api\ExtensionObject\DistributedServiceMap;
use Ecotone\Api\Gateway\DistributedBusHeader;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function in_array;
use function sprintf;

/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
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
            return $this->distributedServiceMap->getAllSubscriptionChannels($this->thisServiceName, $routingKey);
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
