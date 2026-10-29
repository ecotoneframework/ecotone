<?php

declare(strict_types=1);

namespace Ecotone\Modelling\Api\Distribution;

use function array_key_exists;

use Ecotone\Messaging\Attribute\Asynchronous;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\AttributeDefinition;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\Routing\BusRoutingMap;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\MessageHandling\Distribution\UnknownDistributedDestination;

use function in_array;

/**
 * licence Enterprise
 */
final class DistributedServiceMap implements DefinedObject
{
    /**
     * @param array<string, string> $commandMapping - service name -> channel name (for command routing)
     * @param array<string, array{keys: ?array<string>, exclude: array<string>, include: array<string>}> $eventSubscriptions - channel name -> ['keys' => [...] or null, 'exclude' => [...], 'include' => [...]]
     * @param array<object> $distributedBusAnnotations
     * @param bool|null $legacyMode - null = not set, true = legacy (withServiceMapping), false = new API (withCommandMapping/withEventMapping)
     */
    public function __construct(
        private string $referenceName,
        private array $commandMapping = [],
        private array $eventSubscriptions = [],
        private array $distributedBusAnnotations = [],
        private ?bool $legacyMode = null,
    ) {
        Assert::allObjects($this->distributedBusAnnotations, 'Annotations passed to DistributedServiceMap, must all be objects');
    }

    public static function initialize(string $referenceName = DistributedBus::class): self
    {
        return new self($referenceName);
    }

    /**
     * @deprecated Use withCommandMapping() and withEventMapping() instead
     * @param array|null $subscriptionRoutingKeys If null subscribing to all events, if empty array to none, if non empty array then keys will be used to match the name
     */
    public function withServiceMapping(string $serviceName, string $channelName, ?array $subscriptionRoutingKeys = null): self
    {
        $self = clone $this;
        $self->assertNotInNewMode('withServiceMapping');
        $self->legacyMode = true;

        $self->commandMapping[$serviceName] = $channelName;
        $self->eventSubscriptions[$channelName] = [
            'keys' => $subscriptionRoutingKeys,
            'exclude' => [],
            'include' => [],
        ];

        return $self;
    }

    /**
     * Maps a service to a channel for command routing only.
     * Does NOT create any event subscription.
     */
    public function withCommandMapping(string $targetServiceName, string $channelName): self
    {
        $self = clone $this;
        $self->assertNotInLegacyMode('withCommandMapping');
        $self->legacyMode = false;

        $self->commandMapping[$targetServiceName] = $channelName;

        return $self;
    }

    /**
     * Creates an event subscription for a channel with explicit subscription keys.
     *
     * @param string $channelName Target channel to send events to
     * @param array<string> $subscriptionKeys Routing key patterns to match
     * @param array<string> $excludePublishingServices Service names whose events should NOT be sent to this channel
     * @param array<string> $includePublishingServices Service names whose events should ONLY be sent to this channel (whitelist)
     */
    public function withEventMapping(string $channelName, array $subscriptionKeys, array $excludePublishingServices = [], array $includePublishingServices = []): self
    {
        if ($excludePublishingServices !== [] && $includePublishingServices !== []) {
            throw ConfigurationException::create(
                "Cannot use both 'excludePublishingServices' and 'includePublishingServices' in the same event mapping for channel '{$channelName}'. " .
                'These parameters are mutually exclusive - use either exclude (blacklist) or include (whitelist), not both.'
            );
        }

        $self = clone $this;
        $self->assertNotInLegacyMode('withEventMapping');
        $self->legacyMode = false;

        $self->eventSubscriptions[$channelName] = [
            'keys' => $subscriptionKeys,
            'exclude' => $excludePublishingServices,
            'include' => $includePublishingServices,
        ];

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
    public function getCommandMapping(): array
    {
        return $this->commandMapping;
    }

    /**
     * LEGACY MODE ONLY - Get all channels except the one belonging to the given service.
     * Uses service name to channel mapping for exclusion.
     *
     * @deprecated For new mode, use getAllSubscriptionChannels() instead
     */
    public function getAllChannelNamesBesides(string $serviceName, string $routingKey): array
    {
        $filteredChannels = [];
        $excludeChannel = $this->commandMapping[$serviceName] ?? null;

        foreach ($this->eventSubscriptions as $channel => $config) {
            if ($channel === $excludeChannel) {
                continue;
            }

            $keys = $config['keys'];

            if ($keys === null) {
                $filteredChannels[] = $channel;

                continue;
            }

            foreach ($keys as $subscriptionEventFilter) {
                if (BusRoutingMap::globMatch($subscriptionEventFilter, $routingKey)) {
                    $filteredChannels[] = $channel;

                    break;
                }
            }
        }

        return $filteredChannels;
    }

    /**
     * NEW MODE ONLY - Get all subscription channels for an event.
     * Uses explicit exclude/include list from eventSubscriptions config.
     *
     * @param string $sourceServiceName The service publishing the event
     * @param string $routingKey The event routing key
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

    /**
     * @return object[]
     */
    public function getDistributedBusAnnotations(): array
    {
        return $this->distributedBusAnnotations;
    }

    public function isLegacyMode(): bool
    {
        return $this->legacyMode === true;
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
                $this->legacyMode,
            ]
        );
    }

    private function assertNotInLegacyMode(string $methodName): void
    {
        if ($this->legacyMode === true) {
            throw ConfigurationException::create(
                "Cannot use {$methodName}() after withServiceMapping(). " .
                'Use either legacy API (withServiceMapping) or new API (withCommandMapping/withEventMapping), not both.'
            );
        }
    }

    private function assertNotInNewMode(string $methodName): void
    {
        if ($this->legacyMode === false) {
            throw ConfigurationException::create(
                "Cannot use {$methodName}() after withCommandMapping() or withEventMapping(). " .
                'Use either legacy API (withServiceMapping) or new API (withCommandMapping/withEventMapping), not both.'
            );
        }
    }
}
