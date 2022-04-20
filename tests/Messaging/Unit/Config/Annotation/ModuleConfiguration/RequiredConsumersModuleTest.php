<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\Scheduled;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\RequiredConsumersModule;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\Consumer\ExampleConsumer;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Handler\DataReturningService;

/**
 * Class ConverterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RequiredConsumersModuleTest extends AnnotationConfigurationTest
{
    public function test_throwing_exception_if_consumer_was_not_registered()
    {
        $annotationConfiguration = RequiredConsumersModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleConsumer::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->expectException(ConfigurationException::class);

        $configuration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());
    }

    public function test_not_throwing_exception_if_consumer_was_registered_as_message_handler()
    {
        $annotationConfiguration = RequiredConsumersModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleConsumer::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration()
            ->registerConsumerFactory(new PollingConsumerBuilder())
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel("requestChannel"))
            ->registerMessageHandler(
                DataReturningService::createExceptionalServiceActivatorBuilder()
                    ->withEndpointId("someId")
                    ->withInputChannelName("requestChannel")
            );
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $configuration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue(true);
    }

    public function test_not_throwing_exception_if_consumer_was_registered_as_inbound_channel()
    {
        $annotationConfiguration = RequiredConsumersModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleConsumer::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel("requestChannel"))
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    "requestChannel",
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    "executeReturn"
                )->withEndpointId("someId")
            );
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $configuration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue(true);
    }
}