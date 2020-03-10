<?php

namespace Test\Ecotone\Modelling\Unit\Config;

use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Config\AggregateMessageRouterModule;
use Ecotone\Modelling\Config\BusRouterBuilder;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateNoInputChannelAndNoMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\EventHandlerWithClassAndEndpointId;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\QueryHandlerWithClass;

/**
 * Class AggregateMessageRouterModuleTest
 * @package Test\Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageRouterModuleTest extends MessagingTest
{
    public function test_throwing_exception_if_no_command_class_and_input_channel_name_defined()
    {
        $this->expectException(InvalidArgumentException::class);

        AggregateMessageRouterModule::create(InMemoryAnnotationRegistrationService::createFrom([
            AggregateNoInputChannelAndNoMessage::class
        ]));
    }

    public function test_registering_service_command_handler()
    {
        $annotatedClasses = [
            CommandHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], []);
    }

    public function test_registering_service_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            CommandHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], []);
    }

    public function test_registering_aggregate_command_handler()
    {
        $annotatedClasses = [
            \Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\CommandHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            \Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\CommandHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], []);
    }

    public function test_registering_service_query_handler()
    {
        $annotatedClasses = [
            QueryHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, [], $mapping, [], []);
    }

    public function test_registering_aggregate_query_handler()
    {
        $annotatedClasses = [
            \Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\QueryHandlerWithClass::class
        ];
        $mapping = [
            \stdClass::class => [\stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, [], $mapping, [], []);
    }

    public function test_registering_service_event_handler()
    {
        $annotatedClasses = [
            EventHandlerWithClassAndEndpointId::class
        ];
        $mapping = [
            \stdClass::class => ["endpointId.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, []);
    }

    public function test_registering_aggregate_event_handler()
    {
        $annotatedClasses = [
            \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\EventHandlerWithClassAndEndpointId::class
        ];
        $mapping = [
            \stdClass::class => ["endpointId.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, []);
    }

    /**
     * @param array $annotatedClasses
     * @param array $commandMapping
     * @param array $queryMapping
     * @param array $eventObjectMapping
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \ReflectionException
     */
    private function assertRouting(array $annotatedClasses, array $commandMapping, array $queryMapping, array $eventObjectMapping, array $eventNameMapping): void
    {
        $module = AggregateMessageRouterModule::create(InMemoryAnnotationRegistrationService::createFrom($annotatedClasses));

        $extendedConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $module->prepare(
            $extendedConfiguration,
            [],
            ModuleReferenceSearchService::createEmpty()
        );

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByObject($commandMapping))
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByName($commandMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByObject($queryMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByName($queryMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByObject($eventObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByName($eventNameMapping)),
            $extendedConfiguration
        );
    }
}