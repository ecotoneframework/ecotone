<?php

namespace Test\SimplyCodedSoftware\DomainModel\Unit\Config;

use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate\AggregateCommandHandlerExample;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Aggregate\DoStuffCommand;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithNoCommandInformationConfiguration;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\CommandHandler\Service\CommandHandlerWithReturnValue;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler\AggregateQueryHandlerExample;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler\AggregateQueryHandlerWithOutputChannelExample;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler\QueryHandlerWithNoReturnValue;
use Test\SimplyCodedSoftware\DomainModel\Fixture\Annotation\QueryHandler\SomeQuery;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\ConfigurableReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\InMemoryModuleMessaging;
use SimplyCodedSoftware\Messaging\Config\MessagingSystemConfiguration;
use SimplyCodedSoftware\Messaging\Config\OrderedMethodInterceptor;
use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\DomainModel\AggregateMessageConversionServiceBuilder;
use SimplyCodedSoftware\DomainModel\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\DomainModel\Config\AggregateMessagingModule;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Test\SimplyCodedSoftware\DomainModel\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessagingModuleTest extends TestCase
{
    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_configuration_exception_if_command_handler_has_no_information_about_command()
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_command_handler_has_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                CommandHandlerWithReturnValue::class
            ])
        );
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_throwing_exception_if_query_handler_has_no_return_value()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->prepareConfiguration(
            InMemoryAnnotationRegistrationService::createFrom([
                QueryHandlerWithNoReturnValue::class
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_aggregate_command_handler()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith( AggregateCommandHandlerExample::class, "doAction", "\\" . DoStuffCommand::class)
                            ->withInputChannelName("\\" . DoStuffCommand::class)
                            ->withEndpointId('command-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerPreCallMethodInterceptor(
                OrderedMethodInterceptor::create(
                    AggregateMessageConversionServiceBuilder::createWith("\\" . DoStuffCommand::class)
                        ->withEndpointId('command-id'),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR + 1
                )
            );

        $this->createModuleAndAssertConfiguration(
            [
                AggregateCommandHandlerExample::class
            ],
            $expectedConfiguration,
            [
                "\\" . DoStuffCommand::class => "\\" . DoStuffCommand::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_aggregate_query_handler()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith(AggregateQueryHandlerExample::class, "doStuff", "\\" . SomeQuery::class)
                            ->withInputChannelName("\\" . SomeQuery::class)
                            ->withEndpointId('some-id');

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerPreCallMethodInterceptor(OrderedMethodInterceptor::create(
                AggregateMessageConversionServiceBuilder::createWith("\\" . SomeQuery::class)
                    ->withEndpointId('some-id'),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR + 1
            ));

        $this->createModuleAndAssertConfiguration(
            [
                AggregateQueryHandlerExample::class
            ],
            $expectedConfiguration,
            [
                "\\" . SomeQuery::class => "\\" . SomeQuery::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_aggregate_query_handler_with_output_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff", "\\" . SomeQuery::class)
            ->withInputChannelName("\\" . SomeQuery::class)
            ->withEndpointId("some-id")
            ->withOutputMessageChannel("outputChannel");

        $expectedConfiguration = $this->createMessagingSystemConfiguration()
            ->registerMessageHandler($commandHandler)
            ->registerPreCallMethodInterceptor(OrderedMethodInterceptor::create(
                AggregateMessageConversionServiceBuilder::createWith("\\" . SomeQuery::class)
                    ->withEndpointId('some-id'),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR + 1
            ));

        $this->createModuleAndAssertConfiguration(
            [
                AggregateQueryHandlerWithOutputChannelExample::class
            ],
            $expectedConfiguration,
            [
                "\\" . SomeQuery::class => "\\" . SomeQuery::class
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_aggregate_with_custom_input_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff", "\\" . SomeQuery::class)
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
                ->registerPreCallMethodInterceptor(OrderedMethodInterceptor::create(
                    AggregateMessageConversionServiceBuilder::createWith("\\" . SomeQuery::class)
                        ->withEndpointId('some-id'),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR + 1
                )),
            [
                "\\" . SomeQuery::class => "inputChannel"
            ]
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \Exception
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Config\ConfigurationException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_registering_aggregate_without_query_class_with_only_input_channel()
    {
        $commandHandler = AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith( AggregateQueryHandlerWithOutputChannelExample::class, "doStuff", "\\" . SomeQuery::class)
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
                ->registerPreCallMethodInterceptor(OrderedMethodInterceptor::create(
                    AggregateMessageConversionServiceBuilder::createWith("\\" . SomeQuery::class)
                        ->withEndpointId('some-id'),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR + 1
                )),
            [
                "\\" . SomeQuery::class => "inputChannel"
            ]
        );
    }

    /**
     * @return MessagingSystemConfiguration
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function prepareConfiguration(AnnotationRegistrationService $annotationRegistrationService): MessagingSystemConfiguration
    {
        $cqrsMessagingModule = AggregateMessagingModule::create($annotationRegistrationService);

        $extendedConfiguration = $this->createMessagingSystemConfiguration();
        $cqrsMessagingModule->prepare(
            $extendedConfiguration,
            [],
            ConfigurableReferenceSearchService::createEmpty()
        );

        return $extendedConfiguration;
    }
}