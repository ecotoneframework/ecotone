<?php

namespace Ecotone\Amqp\Distribution;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\AmqpBinding;
use Ecotone\Amqp\AmqpExchange;
use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\AmqpQueue;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherConfiguration;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Modelling\Config\DistributedGatewayModule;
use Ecotone\Modelling\DistributedBus;
use Ecotone\Modelling\DistributionEntrypoint;

class AmqpDistributionModule
{
    public const AMQP_DISTRIBUTED_EXCHANGE = 'ecotone.distributed';
    public const AMQP_ROUTING_KEY = 'ecotone.amqp.distributed.service_target';
    public const CHANNEL_PREFIX   = 'distributed_';

    private array $distributedEventHandlers;
    private array $distributedCommandHandlers;

    public function __construct(array $distributedEventHandlers, array $distributedCommandHandlers)
    {
        $this->distributedEventHandlers   = $distributedEventHandlers;
        $this->distributedCommandHandlers = $distributedCommandHandlers;
    }

    public static function create(AnnotationFinder $annotationFinder, InterfaceToCallRegistry $interfaceToCallRegistry): self
    {
        return new self(
            DistributedGatewayModule::getDistributedEventHandlerRoutingKeys($annotationFinder, $interfaceToCallRegistry),
            DistributedGatewayModule::getDistributedCommandHandlerRoutingKeys($annotationFinder, $interfaceToCallRegistry)
        );
    }

    public function getAmqpConfiguration(array $extensionObjects): array
    {
        $applicationConfiguration = $this->getServiceConfiguration($extensionObjects);
        $amqpConfiguration = [];
        /** @var AmqpMessagePublisherConfiguration $distributedBusConfiguration */
        foreach ($extensionObjects as $distributedBusConfiguration) {
            if (! ($distributedBusConfiguration instanceof AmqpDistributedBusConfiguration)) {
                continue;
            }

            if ($distributedBusConfiguration->isPublisher()) {
                $amqpConfiguration[] = AmqpExchange::createTopicExchange(self::AMQP_DISTRIBUTED_EXCHANGE);
            }

            if ($distributedBusConfiguration->isConsumer()) {
                $queueName           = self::CHANNEL_PREFIX . $applicationConfiguration->getServiceName();
                $amqpConfiguration[] = AmqpExchange::createTopicExchange(self::AMQP_DISTRIBUTED_EXCHANGE);
                $amqpConfiguration[] = AmqpQueue::createWith($queueName);
                $amqpConfiguration[] = AmqpBinding::createFromNames(self::AMQP_DISTRIBUTED_EXCHANGE, $queueName, $applicationConfiguration->getServiceName());

                foreach ($this->distributedCommandHandlers as $distributedCommandHandler) {
                    $amqpConfiguration[] = AmqpBinding::createFromNames(self::AMQP_DISTRIBUTED_EXCHANGE, $queueName, $distributedCommandHandler);
                }
                foreach ($this->distributedEventHandlers as $distributedEventHandler) {
                    $amqpConfiguration[] = AmqpBinding::createFromNames(self::AMQP_DISTRIBUTED_EXCHANGE, $queueName, $distributedEventHandler);
                }
            }
        }

        return $amqpConfiguration;
    }

    public function prepare(Configuration $configuration, array $extensionObjects): void
    {
        $registeredReferences     = [];
        $applicationConfiguration = $this->getServiceConfiguration($extensionObjects);

        /** @var AmqpDistributedBusConfiguration $distributedBusConfiguration */
        foreach ($extensionObjects as $distributedBusConfiguration) {
            if (! ($distributedBusConfiguration instanceof AmqpDistributedBusConfiguration)) {
                continue;
            }

            if (in_array($distributedBusConfiguration->getReferenceName(), $registeredReferences)) {
                throw ConfigurationException::create("Registering two publishers under same reference name {$distributedBusConfiguration->getReferenceName()}. You need to create publisher with specific reference using `createWithReferenceName`.");
            }

            if ($distributedBusConfiguration->isPublisher()) {
                Assert::isFalse($applicationConfiguration->getServiceName() === ServiceConfiguration::DEFAULT_SERVICE_NAME, "Service name can't be default when using distribution. Set up correct Service Name");

                $registeredReferences[] = $distributedBusConfiguration->getReferenceName();
                $this->registerPublisher($distributedBusConfiguration, $applicationConfiguration, $configuration);
            }

            if ($distributedBusConfiguration->isConsumer()) {
                Assert::isFalse($applicationConfiguration->getServiceName() === ServiceConfiguration::DEFAULT_SERVICE_NAME, "Service name can't be default when using distribution. Set up correct Service Name");

                $channelName = self::CHANNEL_PREFIX . $applicationConfiguration->getServiceName();
                $configuration->registerMessageChannel(AmqpBackedMessageChannelBuilder::create($channelName, $distributedBusConfiguration->getAmqpConnectionReference()));
                $configuration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withEndpointId($applicationConfiguration->getServiceName())
                        ->withInputChannelName($channelName)
                        ->withOutputMessageChannel(DistributionEntrypoint::DISTRIBUTED_CHANNEL)
                );
            }
        }
    }

    public function canHandle($extensionObject): bool
    {
        return
            $extensionObject instanceof AmqpDistributedBusConfiguration
            || $extensionObject instanceof ServiceConfiguration;
    }

    private function registerPublisher(AmqpDistributedBusConfiguration|AmqpMessagePublisherConfiguration $amqpPublisher, ServiceConfiguration $applicationConfiguration, Configuration $configuration): void
    {
        $mediaType = $amqpPublisher->getOutputDefaultConversionMediaType() ? $amqpPublisher->getOutputDefaultConversionMediaType() : $applicationConfiguration->getDefaultSerializationMediaType();
        $channelName = self::CHANNEL_PREFIX . $applicationConfiguration->getServiceName();

        $configuration
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), DistributedBus::class, 'sendCommand', $amqpPublisher->getReferenceName())
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('command'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('destination', self::AMQP_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), DistributedBus::class, 'convertAndSendCommand', $amqpPublisher->getReferenceName())
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('command'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('destination', self::AMQP_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE, 'command'),
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), DistributedBus::class, 'publishEvent', $amqpPublisher->getReferenceName())
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            GatewayHeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('routingKey', self::AMQP_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                        ]
                    )
            )
            ->registerGatewayBuilder(
                GatewayProxyBuilder::create($amqpPublisher->getReferenceName(), DistributedBus::class, 'convertAndPublishEvent', $amqpPublisher->getReferenceName())
                    ->withParameterConverters(
                        [
                            GatewayPayloadBuilder::create('event'),
                            GatewayHeadersBuilder::create('metadata'),
                            GatewayHeaderBuilder::create('routingKey', DistributionEntrypoint::DISTRIBUTED_ROUTING_KEY),
                            GatewayHeaderBuilder::create('routingKey', self::AMQP_ROUTING_KEY),
                            GatewayHeaderValueBuilder::create(DistributionEntrypoint::DISTRIBUTED_PAYLOAD_TYPE, 'event'),
                        ]
                    )
            )
            ->registerMessageHandler(
                AmqpOutboundChannelAdapterBuilder::create(self::AMQP_DISTRIBUTED_EXCHANGE, $amqpPublisher->getAmqpConnectionReference())
                    ->withEndpointId($amqpPublisher->getReferenceName() . '.handler')
                    ->withInputChannelName($amqpPublisher->getReferenceName())
                    ->withDefaultPersistentMode($amqpPublisher->getDefaultPersistentDelivery())
                    ->withAutoDeclareOnSend(true)
                    ->withHeaderMapper($amqpPublisher->getHeaderMapper())
                    ->withRoutingKeyFromHeader(self::AMQP_ROUTING_KEY)
                    ->withDefaultConversionMediaType($mediaType)
                    ->withStaticHeadersToEnrich([MessageHeaders::POLLED_CHANNEL_NAME => $channelName])
            )
            ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel($amqpPublisher->getReferenceName()));
    }

    private function getServiceConfiguration(array $extensionObjects): ServiceConfiguration
    {
        $applicationConfiguration = ServiceConfiguration::createWithDefaults();
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ServiceConfiguration) {
                $applicationConfiguration = $extensionObject;
                break;
            }
        }

        return $applicationConfiguration;
    }
}
