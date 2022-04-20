<?php

namespace Test\Ecotone\Modelling\Unit\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\Config\BusRouterBuilder;
use Ecotone\Modelling\Config\BusRoutingModule;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagator;
use Ecotone\Modelling\QueryBus;
use ReflectionException;
use stdClass;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithDoubledActionMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithDoubledFactoryMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithFactoryMethod;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithUnionType;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenToRegex;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\EventHandlerForUnionType;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ProjectionEventHandlerExample;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenTo;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenToAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenToToRegex;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithAllowedNotUniqueClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithAllowedNotUniqueClassAndInputChannels;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithNotUniqueClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndObject;
use Test\Ecotone\Modelling\Fixture\Handler\ServiceWithCommandAndQueryHandlersUnderSameClass;
use Test\Ecotone\Modelling\Fixture\Handler\ServiceWithCommandAndQueryHandlersUnderSameName;
use Test\Ecotone\Modelling\Fixture\Order\OrderWasPlaced;

/**
 * Class AggregateMessageRouterModuleTest
 * @package Test\Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BusRoutingModuleTest extends MessagingTest
{
    public function test_registering_service_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => [stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, $mapping, $mapping, [], [], [], []);
    }

    public function test_registering_service_command_handler_for_union_type()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                CommandHandlerWithUnionType::class
            ])
        );
    }

    private function assertRouting(array $annotatedClasses, array $commandObjectMapping, array $commandMapping, array $queryObjectMapping, array $queryMapping, array $eventObjectMapping, array $eventNameMapping): void
    {
        $annotationRegistrationService = InMemoryAnnotationFinder::createFrom($annotatedClasses);
        $extendedConfiguration = $this->prepareModule($annotationRegistrationService);

        $messagePropagator = new MessageHeadersPropagator();
        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        MessageHeadersPropagator::class,
                        InterfaceToCall::create(MessageHeadersPropagator::class, "propagateHeaders"),
                        TransformerBuilder::createWithDirectObject(new MessageHeadersPropagator(), "propagateHeaders")
                            ->withMethodParameterConverters([
                                AllHeadersBuilder::createWith("headers")
                            ]),
                        Precedence::ENDPOINT_HEADERS_PRECEDENCE - 2,
                        CommandBus::class . "||" . EventBus::class . "||" . QueryBus::class . "||" . AsynchronousRunningEndpoint::class
                    )
                )
                ->registerAroundMethodInterceptor(
                    AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                        new MessageHeadersPropagator(),
                        "storeHeaders",
                        Precedence::ENDPOINT_HEADERS_PRECEDENCE - 1,
                        CommandBus::class . "||" . EventBus::class . "||" . QueryBus::class . "||" . AsynchronousRunningEndpoint::class
                    )
                )
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByObject($messagePropagator, $commandObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByName($messagePropagator, $commandMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByObject($messagePropagator, $queryObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByName($messagePropagator, $queryMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByObject($messagePropagator, $eventObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByName($messagePropagator, $eventNameMapping)),
            $extendedConfiguration
        );
    }

    private function prepareModule(AnnotationFinder $annotationRegistrationService): Configuration
    {
        $module = BusRoutingModule::create($annotationRegistrationService, InterfaceToCallRegistry::createEmpty());

        $extendedConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $module->prepare(
            $extendedConfiguration,
            [],
            ModuleReferenceSearchService::createEmpty(),
            InterfaceToCallRegistry::createEmpty()
        );
        return $extendedConfiguration;
    }

    public function test_throwing_configuration_exception_if_command_handler_has_no_information_about_channel()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                CommandHandlerWithNoInputChannelName::class
            ])
        );
    }

    public function test_throwing_exception_when_registering_non_unique_query_handlers()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                ServiceQueryHandlersWithNotUniqueClass::class
            ])
        );
    }

    public function test_throwing_exception_when_query_and_command_are_non_unique_by_class_name()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                ServiceWithCommandAndQueryHandlersUnderSameClass::class
            ])
        );
    }

    public function test_throwing_exception_when_query_and_command_are_non_unique_by_name()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                ServiceWithCommandAndQueryHandlersUnderSameName::class
            ])
        );
    }

    public function test_throwing_exception_when_not_unique_aggregate_factory_methods()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                AggregateCommandHandlerWithDoubledFactoryMethod::class
            ])
        );
    }

    public function test_throwing_exception_when_not_unique_aggregate_action_methods()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                AggregateCommandHandlerWithDoubledActionMethod::class
            ])
        );
    }

    public function test_throwing_when_factory_and_action_channels_are_same_between_different_aggregates()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                AggregateCommandHandlerExample::class,
                AggregateCommandHandlerWithFactoryMethod::class
            ])
        );
    }

    public function test_registering_not_unique_handlers_when_allowed()
    {
        $annotatedClasses = [ServiceQueryHandlersWithAllowedNotUniqueClass::class];
        $mapping = [
            stdClass::class => [stdClass::class]
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, $mapping, [], []);
    }

    public function test_registering_not_unique_handlers_with_input_channels_when_allowed()
    {
        $annotatedClasses = [ServiceQueryHandlersWithAllowedNotUniqueClassAndInputChannels::class];

        $this->assertRouting($annotatedClasses,
            [],
            [
                "some1" => ["some1"],
                "some2" => ["some2"]
            ],
            [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelName::class
        ];

        $this->assertRouting($annotatedClasses, [], ["execute" => ["execute"]], [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelName::class
        ];

        $this->assertRouting($annotatedClasses, [], ["execute" => ["execute"]], [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndObject::class
        ];

        $this->assertRouting($annotatedClasses,
            [], ["execute" => ["execute"]], [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [], ["execute" => ["execute"]], [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [],
            [
                "execute" => ["execute"]
            ]
            , [], [], [], []);
    }

    public function test_registering_service_query_handler()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [stdClass::class => [stdClass::class]], [stdClass::class => [stdClass::class]], [], []);
    }

    public function test_registering_aggregate_query_handler()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [stdClass::class => [stdClass::class]], [stdClass::class => [stdClass::class]], [], []);
    }

    public function test_registering_service_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannel::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], ["execute" => ["execute"]], [], []);
    }

    public function test_registering_aggregate_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannel::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], ["execute" => ["execute"]], [], []);
    }

    public function test_registering_service_query_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannelAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [],
            [
                "execute" => ["execute"]
            ],
            [],
            []
        );
    }

    public function test_registering_service_query_handler_with_input_channel_and_ignore_message()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannelAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [],
            [
                "execute" => ["execute"]
            ],
            [],
            []
        );
    }

    public function test_registering_aggregate_query_handler_with_input_channel_and_ignore_message()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannelAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [],
            [
                "execute" => ["execute"]
            ],
            [],
            []
        );
    }

    public function test_registering_service_event_handler()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => [stdClass::class]], [stdClass::class => [stdClass::class]]);
    }

    public function test_union_registering_service_event_handler()
    {
        $annotatedClasses = [EventHandlerForUnionType::class];

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => [stdClass::class . "|" . \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class], \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class => [stdClass::class . "|" . \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class]], [stdClass::class => [stdClass::class . "|" . \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class], \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class => [stdClass::class . "|" . \Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\OrderWasPlaced::class]]);
    }

    public function test_registering_aggregate_event_handler()
    {
        $annotatedClasses = [
            AggregateEventHandlerWithClass::class
        ];;

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => [stdClass::class]], [stdClass::class => [stdClass::class]]);
    }

    public function test_registering_service_and_aggregate_event_handler_together()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithClass::class,
            AggregateEventHandlerWithClass::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [stdClass::class => [stdClass::class]], [stdClass::class => [stdClass::class]]);
    }

    public function test_registering_service_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], ["execute" => ["execute"]]);
    }

    public function test_registering_aggregate_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], ["execute" => ["execute"]]);
    }

    public function test_registering_service_event_handler_with_listen_to_and_class()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenToAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [],
            [],
            [
                "execute" => ["execute"]
            ]
        );
    }

    public function test_registering_service_event_handler_with_listen_regex()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenToToRegex::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [],
            [],
            [
                "order.*" => ["order.*"]
            ]
        );
    }

    public function test_throwing_exception_if_event_handler_with_listen_regex_registered_for_aggregate()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationFinder::createFrom([
                AggregateEventHandlerWithListenToRegex::class
            ])
        );
    }
}