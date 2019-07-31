<?php

namespace Test\Ecotone\DomainModel\Unit\Config;

use PHPUnit\Framework\TestCase;
use Ecotone\DomainModel\AggregateMessage;
use Ecotone\DomainModel\AggregateMessageConversionServiceBuilder;
use Ecotone\DomainModel\AggregateMessageHandlerBuilder;
use Ecotone\DomainModel\Annotation\CommandHandler;
use Ecotone\DomainModel\Annotation\QueryHandler;
use Ecotone\DomainModel\Config\AggregateMessagingModule;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\InMemoryModuleMessaging;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\PayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerWithReferencesExample;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Aggregate\DoStuffCommand;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoCommandInformationConfiguration;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithReturnValue;
use Test\Ecotone\DomainModel\Fixture\Annotation\CommandHandler\Service\SomeCommand;
use Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler\ExampleEventEventHandler;
use Test\Ecotone\DomainModel\Fixture\Annotation\EventHandler\ExampleEventHandlerWithServices;
use Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler\AggregateQueryHandlerExample;
use Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler\AggregateQueryHandlerWithOutputChannelExample;
use Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler\QueryHandlerWithNoReturnValue;
use Test\Ecotone\DomainModel\Fixture\Annotation\QueryHandler\SomeQuery;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Test\Ecotone\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessagingModuleTest extends TestCase
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_throwing_configuration_exception_if_command_handler_has_no_information_about_command()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                CommandHandlerWithNoCommandInformationConfiguration::class
            ])
        );
    }


    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_throwing_exception_if_query_handler_has_no_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                QueryHandlerWithNoReturnValue::class
            ])
        );
    }

    public function test_resulting_in_exception_when_registering_commands_handlers_for_same_input_channel()
    {
        $this->expectException(ConfigurationException::class);

        $commandHandlerAnnotation = new CommandHandler();

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateCommandHandlerExample::class
            ])
                ->addAnnotationToClassMethod(AggregateCommandHandlerExample::class, "doAnotherAction", $commandHandlerAnnotation)
        );
    }

    public function test_resulting_in_exception_when_registering_query_handlers_for_same_input_channel()
    {
        $this->expectException(ConfigurationException::class);

        $queryHandlerAnnotation = new QueryHandler();

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateQueryHandlerExample::class
            ])
                ->addAnnotationToClassMethod(AggregateQueryHandlerExample::class, "doAnotherAction", $queryHandlerAnnotation)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_command_handler()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith( AggregateCommandHandlerExample::class, "doAction",  DoStuffCommand::class)
                            ->withInputChannelName(DoStuffCommand::class)
                            ->withEndpointId('command-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith( DoStuffCommand::class),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    AggregateCommandHandlerExample::class . "::doAction"
                )
            );

        $this->createModuleAndAssertConfiguration(
            [
                AggregateCommandHandlerExample::class
            ],
            $expectedConfiguration,
            [
                DoStuffCommand::class => DoStuffCommand::class
            ]
        );
    }

    public function __test_registering_service_command_handler_with_return_value()
    {
        $commandHandler = ServiceActivatorBuilder::create( CommandHandlerWithReturnValue::class, "execute")
            ->withMethodParameterConverters([
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("service1", \stdClass::class)
            ])
            ->withInputChannelName("input")
            ->withEndpointId('command-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler);

        $this->createModuleAndAssertConfiguration(
            [
                CommandHandlerWithReturnValue::class
            ],
            $expectedConfiguration,
            [
                SomeCommand::class => "input"
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_service_command_handler()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith( AggregateCommandHandlerExample::class, "doAction",  DoStuffCommand::class)
            ->withInputChannelName(DoStuffCommand::class)
            ->withEndpointId('command-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith( DoStuffCommand::class),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    AggregateCommandHandlerExample::class . "::doAction"
                )
            );

        $this->createModuleAndAssertConfiguration(
            [
                AggregateCommandHandlerExample::class
            ],
            $expectedConfiguration,
            [
                DoStuffCommand::class => DoStuffCommand::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_command_handler_with_extra_services()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith( AggregateCommandHandlerWithReferencesExample::class, "doAction",  DoStuffCommand::class)
            ->withInputChannelName("input")
            ->withMethodParameterConverters([
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("injectedService", \stdClass::class)
            ])
            ->withEndpointId('command-id-with-references');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith( DoStuffCommand::class),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    AggregateCommandHandlerWithReferencesExample::class . "::doAction"
                )
            );

        $this->createModuleAndAssertConfiguration(
            [
                AggregateCommandHandlerWithReferencesExample::class
            ],
            $expectedConfiguration,
            [
                DoStuffCommand::class => "input"
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_query_handler()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(AggregateQueryHandlerExample::class, "doStuff",  SomeQuery::class)
                            ->withInputChannelName( SomeQuery::class)
                            ->withEndpointId('some-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                "",
                AggregateMessageConversionServiceBuilder::createWith( SomeQuery::class),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                AggregateQueryHandlerExample::class . "::doStuff"
            ));

        $this->createModuleAndAssertConfiguration(
            [
                AggregateQueryHandlerExample::class
            ],
            $expectedConfiguration,
            [
                 SomeQuery::class =>  SomeQuery::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_query_handler_with_output_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff",  SomeQuery::class)
            ->withInputChannelName( SomeQuery::class)
            ->withEndpointId("some-id")
            ->withOutputMessageChannel("outputChannel");

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                "",
                AggregateMessageConversionServiceBuilder::createWith( SomeQuery::class),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                AggregateQueryHandlerWithOutputChannelExample::class . "::doStuff"
            ));

        $this->createModuleAndAssertConfiguration(
            [
                AggregateQueryHandlerWithOutputChannelExample::class
            ],
            $expectedConfiguration,
            [
                 SomeQuery::class =>  SomeQuery::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_with_custom_input_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff",  SomeQuery::class)
            ->withInputChannelName("inputChannel")
            ->withEndpointId("some-id");

        $customQueryHandler = new QueryHandler();
        $customQueryHandler->endpointId = "some-id";
        $customQueryHandler->inputChannelName = "inputChannel";

        $this->createModuleWithCustomConfigAndAssertConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateQueryHandlerWithOutputChannelExample::class
            ])
                ->addAnnotationToClassMethod(AggregateQueryHandlerWithOutputChannelExample::class, "doStuff", $customQueryHandler),
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($commandHandler)
                ->registerBeforeMethodInterceptor(\Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith( SomeQuery::class),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    AggregateQueryHandlerWithOutputChannelExample::class . "::doStuff"
                )),
            [
                 SomeQuery::class => "inputChannel"
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_aggregate_without_query_class_with_only_input_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff",  SomeQuery::class)
            ->withInputChannelName("inputChannel")
            ->withEndpointId("some-id");

        $customQueryHandler = new QueryHandler();
        $customQueryHandler->endpointId = "some-id";
        $customQueryHandler->inputChannelName = "inputChannel";

        $this->createModuleWithCustomConfigAndAssertConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                AggregateQueryHandlerWithOutputChannelExample::class
            ])
                ->addAnnotationToClassMethod(AggregateQueryHandlerWithOutputChannelExample::class, "doStuff", $customQueryHandler),
            $this->createMessagingSystemConfiguration()
                ->registerMessageHandler($commandHandler)
                ->registerBeforeMethodInterceptor(MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith( SomeQuery::class),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    AggregateQueryHandlerWithOutputChannelExample::class . "::doStuff"
                )),
            [
                 SomeQuery::class => "inputChannel"
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_service_event_handler()
    {
        $commandHandler = ServiceActivatorBuilder::create( ExampleEventEventHandler::class, "doSomething")
            ->withInputChannelName("someInput")
            ->withEndpointId('some-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel("someInput"));

        $this->createModuleAndAssertConfiguration(
            [
                ExampleEventEventHandler::class
            ],
            $expectedConfiguration,
            [
                DoStuffCommand::class => "someInput"
            ]
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\Config\ConfigurationException
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __test_registering_service_event_handler_with_extra_services()
    {
        $commandHandler = ServiceActivatorBuilder::create( ExampleEventHandlerWithServices::class, "doSomething")
            ->withInputChannelName("someInput")
            ->withMethodParameterConverters([
                PayloadBuilder::create("command"),
                ReferenceBuilder::create("service1", \stdClass::class),
                ReferenceBuilder::create("service2", \stdClass::class)
            ])
            ->withEndpointId('some-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel("someInput"));

        $this->createModuleAndAssertConfiguration(
            [
                ExampleEventHandlerWithServices::class
            ],
            $expectedConfiguration,
            [
                DoStuffCommand::class => "someInput"
            ]
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws \Ecotone\Messaging\MessagingException
     */
    protected function createMessagingSystemConfiguration(): Configuration
    {
        return MessagingSystemConfiguration::prepare(InMemoryModuleMessaging::createEmpty());
    }

    /**
     * @param array $annotationClassesToRegister
     * @param Configuration $expectedConfiguration
     * @param array $messageMapping
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createModuleAndAssertConfiguration(array $annotationClassesToRegister, Configuration $expectedConfiguration, array $messageMapping): void
    {
        $this->assertEquals(
            $expectedConfiguration,
            $this->prepareConfiguration(InMemoryAnnotationRegistrationService::createFrom($annotationClassesToRegister))
        );
    }

    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @param Configuration $expectedConfiguration
     * @param array $messageMapping
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function createModuleWithCustomConfigAndAssertConfiguration(AnnotationRegistrationService $annotationRegistrationService, Configuration $expectedConfiguration, array $messageMapping): void
    {
        $this->assertEquals(
            $expectedConfiguration,
            $this->prepareConfiguration($annotationRegistrationService)
        );
    }

    /**
     * @param AnnotationRegistrationService $annotationRegistrationService
     * @return MessagingSystemConfiguration
     * @throws \Ecotone\Messaging\MessagingException
     */
    private function prepareConfiguration(AnnotationRegistrationService $annotationRegistrationService): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AggregateMessagingModule::create($annotationRegistrationService);

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            [],
            ModuleReferenceSearchService::createEmpty()
        );

        return $extendedConfiguration;
    }
}