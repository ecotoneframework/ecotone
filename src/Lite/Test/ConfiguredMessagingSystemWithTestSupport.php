<?php

declare(strict_types=1);

namespace Ecotone\Lite\Test;

use DateTimeImmutable;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\Container\GatewayProxyMethodReference;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Gateway\MessagingEntrypoint;
use Ecotone\Messaging\Handler\Gateway\Gateway;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;
use Ecotone\Test\StaticPsrClock;
use InvalidArgumentException;
use Psr\Clock\ClockInterface;
use Throwable;

/**
 * licence Apache-2.0
 */
final class ConfiguredMessagingSystemWithTestSupport implements ConfiguredMessagingSystem
{
    public function __construct(private ConfiguredMessagingSystem $configuredMessagingSystem)
    {
    }

    public function getGatewayByName(string $gatewayReferenceName): object
    {
        return $this->configuredMessagingSystem->getGatewayByName($gatewayReferenceName);
    }

    public function getNonProxyGatewayByName(GatewayProxyMethodReference $gatewayProxyMethodReference): Gateway
    {
        return $this->configuredMessagingSystem->getNonProxyGatewayByName($gatewayProxyMethodReference);
    }

    public function runConsoleCommand(string $commandName, array $parameters): mixed
    {
        return $this->configuredMessagingSystem->runConsoleCommand($commandName, $parameters);
    }

    public function getCommandBus(): CommandBus
    {
        return $this->configuredMessagingSystem->getCommandBus();
    }

    public function getQueryBus(): QueryBus
    {
        return $this->configuredMessagingSystem->getQueryBus();
    }

    public function getEventBus(): EventBus
    {
        return $this->configuredMessagingSystem->getEventBus();
    }

    public function getDistributedBus(): DistributedBus
    {
        return $this->configuredMessagingSystem->getDistributedBus();
    }

    public function sendMessage(string $targetChannel, mixed $payload = '', array $metadata = []): mixed
    {
        /** @var MessagingEntrypoint $messagingEntrypoint */
        $messagingEntrypoint = $this->configuredMessagingSystem->getGatewayByName(MessagingEntrypoint::class);

        return $messagingEntrypoint->sendWithHeaders($payload, $metadata, $targetChannel, $metadata[MessageHeaders::ROUTING_SLIP] ?? null);
    }

    public function getMessagePublisher(string $referenceName = MessagePublisher::class): MessagePublisher
    {
        return $this->configuredMessagingSystem->getMessagePublisher($referenceName);
    }

    public function getMessagingTestSupport(): MessagingTestSupport
    {
        return $this->getGatewayByName(MessagingTestSupport::class);
    }

    public function getFlowTestSupport(): FlowTestSupport
    {
        return new FlowTestSupport(
            $this->getCommandBus(),
            $this->getEventBus(),
            $this->getQueryBus(),
            $this->getServiceFromContainer(AggregateDefinitionRegistry::class),
            $this->getMessagingTestSupport(),
            $this->getGatewayByName(MessagingEntrypoint::class),
            $this->getServiceFromContainer(EcotoneClockInterface::class),
            $this->configuredMessagingSystem
        );
    }

    /**
     * @template T
     * @param class-string<T> $referenceName
     * @return T
     */
    public function getServiceFromContainer(string $referenceName): object
    {
        return $this->configuredMessagingSystem->getServiceFromContainer($referenceName);
    }

    public function getMessageChannelByName(string $channelName): MessageChannel
    {
        return $this->configuredMessagingSystem->getMessageChannelByName($channelName);
    }

    public function run(string $name, ?ExecutionPollingMetadata $executionPollingMetadata = null): void
    {
        $this->configuredMessagingSystem->run($name, $executionPollingMetadata);
    }

    public function list(): array
    {
        return $this->configuredMessagingSystem->list();
    }

    public function getGatewayList(): array
    {
        return $this->configuredMessagingSystem->getGatewayList();
    }

    public function replaceWith(ConfiguredMessagingSystem $messagingSystem): void
    {
        $this->configuredMessagingSystem->replaceWith($messagingSystem);
    }

    public function changeTime(DateTimeImmutable|Duration $time): self
    {
        $psrClock = $this->getStaticPsrClockFromContainer();

        if ($time instanceof Duration) {
            $psrClock->setCurrentTime(
                DateTimeImmutable::createFromInterface($psrClock->now())->modify("+{$time->inMicroseconds()} microseconds")
            );
            return $this;
        }

        if ($psrClock->hasBeenChanged() && $time <= $psrClock->now()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot move time backwards. Current clock time: %s, requested time: %s',
                    $psrClock->now()->format('Y-m-d H:i:s.u'),
                    $time->format('Y-m-d H:i:s.u')
                )
            );
        }

        $psrClock->setCurrentTime($time);

        return $this;
    }

    private function getStaticPsrClockFromContainer(): StaticPsrClock
    {
        try {
            $psrClock = $this->configuredMessagingSystem->getServiceFromContainer(ClockInterface::class);
        } catch (Throwable) {
            throw new InvalidArgumentException(
                'Changing time is only possible when using StaticPsrClock as the ClockInterface. ' .
                'Register ClockInterface::class => new StaticPsrClock() in your container services.'
            );
        }

        if (! $psrClock instanceof StaticPsrClock) {
            throw new InvalidArgumentException(
                'Changing time is only possible when using StaticPsrClock as the ClockInterface. ' .
                'Register ClockInterface::class => new StaticPsrClock() in your container services.'
            );
        }

        return $psrClock;
    }
}
