<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\Collector\Config\CollectorConfiguration;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\AsynchronousModule;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\MessagingException;
use ReflectionException;
use RuntimeException;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncClassExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncCommandHandlerWithoutIdExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncEventHandlerExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncEventHandlerWithoutIdExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncMethodExample;
use Test\Ecotone\Messaging\Fixture\Annotation\Async\AsyncQueryHandlerExample;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;
use Test\Ecotone\Modelling\Fixture\Retry\RetriedCommandHandler;

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
final class AsynchronousModuleTest extends AnnotationConfigurationTestCase
{
    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_registering_async_channel_for_method()
    {
        $annotationConfiguration = AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncMethodExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerAsynchronousEndpoint('asyncChannel', 'asyncServiceActivator'),
            $configuration
        );
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_registering_async_channel_for_whole_class()
    {
        $annotationConfiguration = AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncClassExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerAsynchronousEndpoint('asyncChannel1', 'asyncServiceActivator1')
                ->registerAsynchronousEndpoint('asyncChannel2', 'asyncServiceActivator2'),
            $configuration
        );
    }

    public function test_registering_event_handler()
    {
        $annotationConfiguration = AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncEventHandlerExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration()
                ->registerAsynchronousEndpoint('asyncChannel', 'asyncEvent'),
            $configuration
        );
    }

    public function test_ignoring_query_handler_as_async()
    {
        $annotationConfiguration = AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncQueryHandlerExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $this->createMessagingSystemConfiguration(),
            $configuration
        );
    }

    /**
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_using_generated_id_for_event_handler()
    {
        $this->expectException(ConfigurationException::class);

        AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncEventHandlerWithoutIdExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_throwing_exception_if_using_generated_id_for_command_handler()
    {
        $this->expectException(ConfigurationException::class);

        AsynchronousModule::create(
            InMemoryAnnotationFinder::createEmpty()
                ->registerClassWithAnnotations(AsyncCommandHandlerWithoutIdExample::class),
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_messaging_provide_default_ending_polling_metadata()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class],
            [new OrderService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('orders', conversionMediaType: MediaType::createApplicationXPHP()),
            ],
            []
        );

        $ecotoneLite->sendCommand(new PlaceOrder('1'));

        /** This otherwise would not finish */
        $ecotoneLite->run('orders');

        $this->assertCount(1, $ecotoneLite->sendQueryWithRouting('order.getOrders'));
    }

    public function test_messaging_provide_default_ending_on_failure_polling_metadata()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [RetriedCommandHandler::class],
            [new RetriedCommandHandler()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('async', conversionMediaType: MediaType::createApplicationXPHP()),
            ],
            []
        );

        $ecotoneLite->sendCommandWithRoutingKey('retried.asynchronous', 2);

        $this->expectException(RuntimeException::class);

        $ecotoneLite->run('async');
    }

    /**
     * @param string[] $classesToResolve
     * @param object[] $services
     * @param MessageChannelBuilder[] $channelBuilders
     * @param CollectorConfiguration[] $collectorConfigurations
     */
    private function bootstrapEcotone(array $classesToResolve, array $services, array $channelBuilders, array $collectorConfigurations): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects($collectorConfigurations),
            enableAsynchronousProcessing: $channelBuilders
        );
    }
}
