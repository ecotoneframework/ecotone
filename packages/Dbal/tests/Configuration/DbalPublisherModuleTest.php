<?php


namespace Test\Ecotone\Dbal\Configuration;


use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Dbal\Configuration\DbalPublisherModule;
use Ecotone\Dbal\Configuration\DbalMessagePublisherConfiguration;
use Ecotone\Dbal\DbalOutboundChannelAdapterBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
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

class DbalPublisherModuleTest extends TestCase
{
    public function test_registering_single_amqp_publisher()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "send", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "sendWithMetadata", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "convertAndSend", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "convertAndSendWithMetadata", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerMessageHandler(
                    DbalOutboundChannelAdapterBuilder::create("queueName", "connection")
                        ->withEndpointId(MessagePublisher::class . ".handler")
                        ->withInputChannelName(MessagePublisher::class)
                        ->withAutoDeclareOnSend(false)
                        ->withHeaderMapper("ecotone.*")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                ),
            $this->prepareConfiguration(
                [
                    DbalMessagePublisherConfiguration::create(MessagePublisher::class, "queueName", MediaType::APPLICATION_JSON, "connection")
                        ->withAutoDeclareQueueOnSend(false)
                        ->withHeaderMapper("ecotone.*")
                ]
            )
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
        $cqrsMessagingModule = DbalPublisherModule::create(InMemoryAnnotationFinder::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $moduleReferenceSearchService = ModuleReferenceSearchService::createEmpty();

        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            $extensions,
            $moduleReferenceSearchService,
            InterfaceToCallRegistry::createEmpty()
        );

        return $extendedConfiguration;
    }

    public function test_registering_single_dbal_publisher_with_application_conversion_media_type()
    {
        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "send", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "sendWithMetadata", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderBuilder::create("sourceMediaType", MessageHeaders::CONTENT_TYPE)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "convertAndSend", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerGatewayBuilder(
                    GatewayProxyBuilder::create(MessagePublisher::class, MessagePublisher::class, "convertAndSendWithMetadata", MessagePublisher::class)
                        ->withParameterConverters([
                            GatewayPayloadBuilder::create("data"),
                            GatewayHeadersBuilder::create("metadata"),
                            GatewayHeaderValueBuilder::create(MessageHeaders::CONTENT_TYPE, MediaType::APPLICATION_X_PHP)
                        ])
                )
                ->registerMessageHandler(
                    DbalOutboundChannelAdapterBuilder::create("queueName", "connection")
                        ->withEndpointId(MessagePublisher::class . ".handler")
                        ->withInputChannelName(MessagePublisher::class)
                        ->withAutoDeclareOnSend(true)
                        ->withHeaderMapper("")
                        ->withDefaultConversionMediaType(MediaType::APPLICATION_JSON)
                ),
            $this->prepareConfiguration(
                [
                    DbalMessagePublisherConfiguration::create(MessagePublisher::class, "queueName", MediaType::APPLICATION_JSON, "connection"),
                    ServiceConfiguration::createWithDefaults()
                        ->withDefaultSerializationMediaType(MediaType::APPLICATION_JSON)
                ]
            )
        );
    }

    public function test_throwing_exception()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareConfiguration(
            [
                DbalMessagePublisherConfiguration::create("test", MessagePublisher::class, MediaType::APPLICATION_JSON, "connection"),
                DbalMessagePublisherConfiguration::create("test", MessagePublisher::class, MediaType::APPLICATION_JSON, "connection")
            ]
        );
    }
}