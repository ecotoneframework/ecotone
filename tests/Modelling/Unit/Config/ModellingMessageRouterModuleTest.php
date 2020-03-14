<?php

namespace Test\Ecotone\Modelling\Unit\Config;

use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Config\ModellingMessageRouterModule;
use Ecotone\Modelling\Config\BusRouterBuilder;
use stdClass;
use Test\Ecotone\Messaging\Unit\MessagingTest;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\AggregateNoInputChannelAndNoMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Aggregate\ServiceCommandHandlerWithInputChannelNameAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\AggregateCommandHandlerWithInputChannelNameAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoInputChannelName;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenTo;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Aggregate\AggregateEventHandlerWithListenToAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenTo;
use Test\Ecotone\Modelling\Fixture\Annotation\EventHandler\Service\ServiceEventHandlerWithListenToAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Aggregate\AggregateQueryHandlerWithInputChannelAndObject;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlersWithNotUniqueClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithClass;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannel;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndIgnoreMessage;
use Test\Ecotone\Modelling\Fixture\Annotation\QueryHandler\Service\ServiceQueryHandlerWithInputChannelAndObject;

/**
 * Class AggregateMessageRouterModuleTest
 * @package Test\Ecotone\Modelling\Config
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ModellingMessageRouterModuleTest extends MessagingTest
{
    public function test_registering_service_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => ["commandHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], [], [], []);
    }

    public function test_resulting_in_exception_when_registering_commands_handlers_for_same_input_channel()
    {
        $this->expectException(ConfigurationException::class);

        $commandHandlerAnnotation = new CommandHandler();

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateCommandHandlerExample::class
            ])
                ->addAnnotationToClassMethod(AggregateCommandHandlerExample::class, "doAnotherAction", $commandHandlerAnnotation)
        );
    }

    public function test_throwing_configuration_exception_if_command_handler_has_no_information_about_channel()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                CommandHandlerWithNoInputChannelName::class
            ])
        );
    }

    public function test_throwing_exception_when_registering_non_unique_query_handlers()
    {
        $this->expectException(ConfigurationException::class);

        $this->prepareModule(
            InMemoryAnnotationRegistrationService::createFrom([
                ServiceQueryHandlersWithNotUniqueClass::class
            ])
        );
    }

    private function assertRouting(array $annotatedClasses, array $commandObjectMapping, array $commandMapping, array $queryObjectMapping, array $queryMapping, array $eventObjectMapping, array $eventNameMapping): void
    {
        $annotationRegistrationService = InMemoryAnnotationRegistrationService::createFrom($annotatedClasses);
        $extendedConfiguration = $this->prepareModule($annotationRegistrationService);

        $this->assertEquals(
            MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty())
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByObject($commandObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createCommandBusByName($commandMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByObject($queryObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createQueryBusByName($queryMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByObject($eventObjectMapping))
                ->registerMessageHandler(BusRouterBuilder::createEventBusByName($eventNameMapping)),
            $extendedConfiguration
        );
    }

    public function test_registering_aggregate_command_handler_with_endpoint_id()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => ["commandHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, $mapping, [], [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelName::class
        ];
        $mapping = [
            "execute" => ["commandHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], $mapping, [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelName::class
        ];
        $mapping = [
            "execute" => ["commandHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], $mapping, [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndObject::class
        ];

        $this->assertRouting($annotatedClasses,
            [
                \stdClass::class => ["commandHandler.target"]
            ],
            [
                "execute" => ["commandHandler.target"]
            ],
            [], [], [], []);
    }

    public function test_registering_service_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            AggregateCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [],
            [
                "execute" => ["commandHandler.target"]
            ],
            [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelNameAndObject::class
        ];

        $this->assertRouting($annotatedClasses,
            [
                \stdClass::class => ["commandHandler.target"]
            ],
            [
                "execute" => ["commandHandler.target"]
            ]
        , [], [], [], []);
    }

    public function test_registering_aggregate_command_handler_with_input_channel_and_ignore_class()
    {
        $annotatedClasses = [
            ServiceCommandHandlerWithInputChannelNameAndIgnoreMessage::class
        ];

        $this->assertRouting($annotatedClasses,
            [],
            [
                "execute" => ["commandHandler.target"]
            ]
            , [], [], [], []);
    }

    public function test_registering_service_query_handler()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => ["queryHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, [], [], []);
    }

    public function test_registering_aggregate_query_handler()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => ["queryHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], $mapping, [], [], []);
    }

    public function test_registering_service_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannel::class
        ];
        $mapping = [
            "execute" => ["queryHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], $mapping, [], []);
    }

    public function test_registering_aggregate_query_handler_with_input_channel()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannel::class
        ];
        $mapping = [
            "execute" => ["queryHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], $mapping, [], []);
    }

    public function test_registering_service_query_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            ServiceQueryHandlerWithInputChannelAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [
                \stdClass::class => ["queryHandler.target"]
            ],
            [
                "execute" => ["queryHandler.target"]
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
                "execute" => ["queryHandler.target"]
            ],
            [],
            []
        );
    }

    public function test_registering_aggregate_query_handler_with_input_channel_and_class()
    {
        $annotatedClasses = [
            AggregateQueryHandlerWithInputChannelAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [],
            [
                \stdClass::class => ["queryHandler.target"]
            ],
            [
                "execute" => ["queryHandler.target"]
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
                "execute" => ["queryHandler.target"]
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
        $mapping = [
            stdClass::class => ["eventHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], $mapping, []);
    }

    public function test_registering_aggregate_event_handler()
    {
        $annotatedClasses = [
            AggregateEventHandlerWithClass::class
        ];
        $mapping = [
            stdClass::class => ["eventHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], $mapping, []);
    }

    public function test_registering_service_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];
        $mapping = [
            "execute" => ["eventHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], $mapping);
    }

    public function test_registering_aggregate_event_handler_with_listen_to()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenTo::class
        ];
        $mapping = [
            "execute" => ["eventHandler.target"]
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [], [], $mapping);
    }

    public function test_registering_service_event_handler_with_listen_to_and_class()
    {
        $annotatedClasses = [
            ServiceEventHandlerWithListenToAndObject::class
        ];

        $this->assertRouting($annotatedClasses, [], [], [], [],
            [
                \stdClass::class => ["eventHandler.target"]
            ],
            [
                "execute" => ["eventHandler.target"]
            ]
        );
    }

    /**
     * @param InMemoryAnnotationRegistrationService $annotationRegistrationService
     * @return \Ecotone\Messaging\Config\Configuration
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Ecotone\Messaging\MessagingException
     * @throws \ReflectionException
     */
    private function prepareModule(InMemoryAnnotationRegistrationService $annotationRegistrationService): \Ecotone\Messaging\Config\Configuration
    {
        $module = ModellingMessageRouterModule::create($annotationRegistrationService);

        $extendedConfiguration = MessagingSystemConfiguration::prepareWithDefaults(InMemoryModuleMessaging::createEmpty());
        $module->prepare(
            $extendedConfiguration,
            [],
            ModuleReferenceSearchService::createEmpty()
        );
        return $extendedConfiguration;
    }
}