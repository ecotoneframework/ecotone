<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\RequiredConsumersModule;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Test\Ecotone\Messaging\Fixture\Annotation\Consumer\ExampleConsumer;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;

/**
 * Class ConverterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class RequiredConsumersModuleTestCase extends AnnotationConfigurationTestCase
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

    public function test_not_throwing_exception_if_consumer_was_registered_as_inbound_channel()
    {
        $annotationConfiguration = RequiredConsumersModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(ExampleConsumer::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration()
            ->registerMessageChannel(SimpleMessageChannelBuilder::createQueueChannel('requestChannel'))
            ->registerConsumer(
                InboundChannelAdapterBuilder::createWithDirectObject(
                    'requestChannel',
                    ConsumerContinuouslyWorkingService::createWithReturn(5),
                    InterfaceToCall::create(ConsumerContinuouslyWorkingService::class, 'executeReturn')
                )->withEndpointId('someId')
            );
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $configuration->buildMessagingSystemFromConfiguration(InMemoryReferenceSearchService::createEmpty());

        $this->assertTrue(true);
    }
}
