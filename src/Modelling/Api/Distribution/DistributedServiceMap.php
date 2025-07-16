<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Api\Distribution;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\MessageHandling\Distribution\UnknownDistributedDestination;

/**
 * licence Enterprise
 */
final class DistributedServiceMap implements DefinedObject
{
    /**
     * @param array<string, string> $serviceMapping
     * @param array<string, array<string>> $subscriptionRoutingKeys
     * @param array<object> $distributedBusAnnotations
     */
    public function __construct(
        private string $referenceName,
        private array $serviceMapping = [],
        private ?array $subscriptionRoutingKeys = null,
        private array $distributedBusAnnotations = [],
    ) {
        Assert::allObjects($this->distributedBusAnnotations, 'Annotations passed to DistributedServiceMap, must all be objects');
    }

    public static function initialize(string $referenceName = DistributedBus::class): self
    {
        return new self($referenceName);
    }

    /**
     * @param array|null $subscriptionRoutingKeys If null subscribing to all events, if empty array to none, if non empty array then keys will be used to match the name
     */
    public function withServiceMapping(string $serviceName, string $channelName, ?array $subscriptionRoutingKeys = null): self
    {
        $self = clone $this;
        $self->serviceMapping[$serviceName] = $channelName;
        $self->subscriptionRoutingKeys[$serviceName] = $subscriptionRoutingKeys;

        return $self;
    }

    public function withAsynchronousChannel(string $channelName): self
    {
        $self = clone $this;
        $self->distributedBusAnnotations[] = new AttributeDefinition(Asynchronous::class, [$channelName]);

        return $self;
    }

    /**
     * @return array<string, string>
     */
    public function getServiceMapping(): array
    {
        return $this->serviceMapping;
    }

    public function getAllChannelNamesBesides(string $serviceName, string $routingKey): array
    {
        $filteredChannels = [];

        foreach ($this->serviceMapping as $service => $channel) {
            if ($service !== $serviceName) {
                if ($this->subscriptionRoutingKeys[$service] === null) {
                    $filteredChannels[] = $channel;

                    continue;
                }

                foreach ($this->subscriptionRoutingKeys[$service] as $subscriptionEventFilter) {
                    if (BusRoutingMap::globMatch($subscriptionEventFilter, $routingKey)) {
                        $filteredChannels[] = $channel;

                        break;
                    }
                }
            }
        }

        return $filteredChannels;
    }

    public function getChannelNameFor(string $serviceName): string
    {
        if (! array_key_exists($serviceName, $this->serviceMapping)) {
            throw new UnknownDistributedDestination("Service {$serviceName} is not registered in distributed service map");
        }

        return $this->serviceMapping[$serviceName];
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    /**
     * @return object[]
     */
    public function getDistributedBusAnnotations(): array
    {
        return $this->distributedBusAnnotations;
    }

    public function getDefinition(): Definition
    {
        return Definition::createFor(
            self::class,
            [
                $this->referenceName,
                $this->serviceMapping,
                $this->subscriptionRoutingKeys,
                $this->distributedBusAnnotations,
            ]
        );
    }
}
