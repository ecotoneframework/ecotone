<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use function array_key_exists;

use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\MessageHandling\Distribution\UnknownDistributedDestination;

use function in_array;

/**
 * licence Enterprise
 *
 * Use requires an Ecotone Enterprise licence (see LICENSE-ENTERPRISE; trial: https://ecotone.tech/pricing).
 */
final class DistributedServiceMap implements DefinedObject
{
    /**
     * @param array<string, string> $commandMapping - service name -> channel name (for command routing)
     * @param array<string, array{keys: array<string>, exclude: array<string>, include: array<string>}> $eventSubscriptions - channel name -> ['keys' => [...], 'exclude' => [...], 'include' => [...]]
     * @param array<object> $distributedBusAnnotations
     */
    public function __construct(
        private readonly string $referenceName,
        public readonly array $commandMapping = [],
        public readonly array $eventSubscriptions = [],
        public readonly array $distributedBusAnnotations = [],
    ) {
        Assert::allObjects($this->distributedBusAnnotations, 'Annotations passed to DistributedServiceMap, must all be objects');
    }

    public static function initialize(string $referenceName = DistributedBus::class): self
    {
        return new self($referenceName);
    }

    public function withCommandMapping(string $targetServiceName, string $channelName): self
    {
        return new self(...[...get_object_vars($this), 'commandMapping' => array_replace($this->commandMapping, [$targetServiceName => $channelName])]);
    }

    /**
     * @param array<string> $subscriptionKeys Routing key patterns to match
     * @param array<string> $excludePublishingServices Service names whose events should NOT be sent to this channel
     * @param array<string> $includePublishingServices Service names whose events should ONLY be sent to this channel (whitelist)
     *
     * @link https://docs.ecotone.tech/modelling/microservices-php/distributed-bus/distributed-bus-with-service-map/configuration#register-service-map-for-publishing
     */
    public function withEventMapping(string $channelName, array $subscriptionKeys, array $excludePublishingServices = [], array $includePublishingServices = []): self
    {
        if ($excludePublishingServices !== [] && $includePublishingServices !== []) {
            throw ConfigurationException::create(
                "Cannot use both 'excludePublishingServices' and 'includePublishingServices' in the same event mapping for channel '{$channelName}'. " .
                'These parameters are mutually exclusive - use either exclude (blacklist) or include (whitelist), not both.'
            );
        }

        return new self(...[...get_object_vars($this), 'eventSubscriptions' => array_replace($this->eventSubscriptions, [
            $channelName => [
                'keys' => $subscriptionKeys,
                'exclude' => $excludePublishingServices,
                'include' => $includePublishingServices,
            ],
        ])]);
    }

    public function withAsynchronousChannel(string $channelName): self
    {
        return new self(...[...get_object_vars($this), 'distributedBusAnnotations' => [...$this->distributedBusAnnotations, new AttributeDefinition(Asynchronous::class, [$channelName])]]);
    }

    /**
     * @return array<string>
     */
    public function getAllSubscriptionChannels(string $sourceServiceName, string $routingKey): array
    {
        $filteredChannels = [];

        foreach ($this->eventSubscriptions as $channel => $config) {
            $keys = $config['keys'];
            $exclude = $config['exclude'];
            $include = $config['include'];

            if (in_array($sourceServiceName, $exclude, true)) {
                continue;
            }

            if ($include !== [] && ! in_array($sourceServiceName, $include, true)) {
                continue;
            }

            foreach ($keys as $subscriptionEventFilter) {
                if (BusRoutingMap::globMatch($subscriptionEventFilter, $routingKey)) {
                    $filteredChannels[] = $channel;

                    break;
                }
            }
        }

        return array_unique($filteredChannels);
    }

    public function getChannelNameFor(string $serviceName): string
    {
        if (! array_key_exists($serviceName, $this->commandMapping)) {
            throw new UnknownDistributedDestination("Service {$serviceName} is not registered in distributed service map");
        }

        return $this->commandMapping[$serviceName];
    }

    public function getReferenceName(): string
    {
        return $this->referenceName;
    }

    public function getDefinition(): Definition
    {
        return Definition::createFor(
            self::class,
            [
                $this->referenceName,
                $this->commandMapping,
                $this->eventSubscriptions,
                $this->distributedBusAnnotations,
            ]
        );
    }
}
