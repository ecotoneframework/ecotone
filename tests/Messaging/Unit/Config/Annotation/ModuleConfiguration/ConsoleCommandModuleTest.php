<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Messaging\Config\Annotation\InMemoryAnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\InboundChannelAdapterModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\OneTimeCommandParameter;
use Ecotone\Messaging\Endpoint\InboundChannelAdapter\InboundChannelAdapterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\InboundChannelAdapter\InboundChannelAdapterExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\DefaultParametersOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\NoParameterOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithConstructorParametersCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithIncorrectResultSet;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithResultExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ParametersOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ParametersWithReferenceOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ReferenceBasedConsoleCommand;

/**
 * Class InboundChannelAdapterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsoleCommandModuleTest extends AnnotationConfigurationTest
{
    public function test_creating_no_parameter_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                NoParameterOneTimeCommandExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", []))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(new NoParameterOneTimeCommandExample(), "execute")
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_registering_reference_based_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ReferenceBasedConsoleCommand::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", []))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create("consoleCommand", "execute")
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_creating_with_result_set()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                OneTimeWithResultExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", []))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(new OneTimeWithResultExample(), "execute")
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_creating_with_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ParametersOneTimeCommandExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", [OneTimeCommandParameter::create("name"), OneTimeCommandParameter::create("surname")]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(new ParametersOneTimeCommandExample(), "execute")
                        ->withMethodParameterConverters([
                            HeaderBuilder::create("name", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "name"),
                            HeaderBuilder::create("surname", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "surname")
                        ])
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_creating_with_default_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                DefaultParametersOneTimeCommandExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", [OneTimeCommandParameter::create("name"), OneTimeCommandParameter::createWithDefaultValue("surname", "cash")]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(new DefaultParametersOneTimeCommandExample(), "execute")
                        ->withMethodParameterConverters([
                            HeaderBuilder::create("name", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "name"),
                            HeaderBuilder::create("surname", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "surname")
                        ])
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_creating_with_reference_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ParametersWithReferenceOneTimeCommandExample::class
            ])
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create("ecotone.channel.doSomething", "doSomething", [OneTimeCommandParameter::create("name"), OneTimeCommandParameter::create("surname")]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::createWithDirectReference(new ParametersWithReferenceOneTimeCommandExample(), "execute")
                        ->withMethodParameterConverters([
                            HeaderBuilder::create("name", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "name"),
                            HeaderBuilder::create("surname", ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . "surname"),
                            ReferenceBuilder::create("object", \stdClass::class)
                        ])
                        ->withEndpointId("ecotone.endpoint.doSomething")
                        ->withInputChannelName("ecotone.channel.doSomething")
                )
        );
    }

    public function test_throwing_exception_when_one_time_command_having_incorrect_return_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                OneTimeWithIncorrectResultSet::class
            ])
        );
    }
}