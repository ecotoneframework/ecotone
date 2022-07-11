<?php

namespace Test\Ecotone\Amqp\Configuration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Amqp\AmqpOutboundChannelAdapterBuilder;
use Ecotone\Amqp\Publisher\AmqpMessagePublisherConfiguration;
use Ecotone\Amqp\Publisher\AmqpPublisherModule;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderValueBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagePublisher;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * @internal
 */
class AmqpPublisherModuleTest extends TestCase
{
    public function test_registering_single_amqp_publisher()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'send', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'sendWithMetadata', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'convertAndSend', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'convertAndSendWithMetadata', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerMessageHandler(
                    AmqpOutboundChannelAdapterBuilder::create('exchangeName', 'amqpConnection')
                        ->withEndpointId(MessagePublisher::class . '.handler')
                        ->withInputChannelName(MessagePublisher::class)
                        ->withRoutingKeyFromHeader('amqpRouting')
                        ->withDefaultPersistentMode(false)
                        ->withAutoDeclareOnSend(true)
                        ->withHeaderMapper('ecotone.*')
                        ->withDefaultRoutingKey('someRouting')
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                )
                ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(MessagePublisher::class)),
            $this->prepareConfiguration(
                [
                    AmqpMessagePublisherConfiguration::create(MessagePublisher::class, 'exchangeName', MediaType::APPLICATION_JSON, 'amqpConnection')
                        ->withRoutingKeyFromHeader('amqpRouting')
                        ->withAutoDeclareQueueOnSend(true)
                        ->withHeaderMapper('ecotone.*')
                        ->withDefaultRoutingKey('someRouting')
                        ->withDefaultPersistentDelivery(false),
                ]
            )
        );
    }

    public function test_registering_single_amqp_publisher_with_application_conversion_media_type()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'send', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'sendWithMetadata', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderBuilder::create('sourceMediaType', MessageHeaders::CONTENT_TYPE),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'convertAndSend', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, 'convertAndSendWithMetadata', MessagePublisher::class)
                        ->withParameterConverters(
                            [
                                GatewayPayloadBuilder::create('data'),
                                GatewayHeadersBuilder::create('metadata'),
                                GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP),
                            ]
                        )
                )
                ->registerMessageHandler(
                    AmqpOutboundChannelAdapterBuilder::create('exchangeName', 'amqpConnection')
                        ->withEndpointId(MessagePublisher::class . '.handler')
                        ->withInputChannelName(MessagePublisher::class)
                        ->withRoutingKeyFromHeader('amqpRouting')
                        ->withDefaultPersistentMode(true)
                        ->withAutoDeclareOnSend(true)
                        ->withHeaderMapper('')
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                )
                ->registerMessageChannel(SimpleMessageChannelBuilder::createDirectMessageChannel(MessagePublisher::class)),
            $this->prepareConfiguration(
                [
                    AmqpMessagePublisherConfiguration::create(MessagePublisher::class, 'exchangeName', null, 'amqpConnection')
                        ->withRoutingKeyFromHeader('amqpRouting')
                        ->withAutoDeclareQueueOnSend(true),
                    ServiceConfiguration::createWithDefaults()
                        ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON),
                ]
            )
        );
    }

    public function test_throwing_exception()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareConfiguration(
            [
                AmqpMessagePublisherConfiguration::create('test', MessagePublisher::class, MediaType::APPLICATION_JSON, 'amqpConnection'),
                AmqpMessagePublisherConfiguration::create('test', MessagePublisher::class, MediaType::APPLICATION_JSON, 'amqpConnection'),
            ]
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $extensions
     *
     * @return MessagingSystemConfiguration
     * @throws MessagingException
     * @throws AnnotationException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    private function prepareConfiguration(array $extensions): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AmqpPublisherModule::create(InMemoryAnnotationFinder::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $extendedConfiguration        = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService,
            InterfaceToCallRegistry::createEmpty()
        );

        return $extendedConfiguration;
    }
}
