<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\InMemory\InMemoryAnnotationFinder;
use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ConsoleCommandModule;
use Ecotone\Messaging\Config\ConsoleCommandConfiguration;
use Ecotone\Messaging\Config\ConsoleCommandParameter;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Support\InvalidArgumentException;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ConsoleCommandWithArrayOptions;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ConsoleCommandWithMessageHeaders;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\DefaultParametersOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithIncorrectResultSet;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithResultExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ParametersOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ParametersWithReferenceOneTimeCommandExample;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\ReferenceBasedConsoleCommand;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\StdClassConverter;

/**
 * Class InboundChannelAdapterModuleTest
 * @package Test\Ecotone\Messaging\Unit\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class ConsoleCommandModuleTest extends AnnotationConfigurationTest
{
    public function test_registering_reference_based_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ReferenceBasedConsoleCommand::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create('ecotone.channel.doSomething', 'doSomething', []))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create('consoleCommand', InterfaceToCall::create(ReferenceBasedConsoleCommand::class, 'execute'))
                        ->withEndpointId('ecotone.endpoint.doSomething')
                        ->withInputChannelName('ecotone.channel.doSomething')
                )
        );
    }

    public function test_creating_with_result_set()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                OneTimeWithResultExample::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create('ecotone.channel.doSomething', 'doSomething', []))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(OneTimeWithResultExample::class, InterfaceToCall::create(OneTimeWithResultExample::class, 'execute'))
                        ->withEndpointId('ecotone.endpoint.doSomething')
                        ->withInputChannelName('ecotone.channel.doSomething')
                )
        );
    }

    public function test_creating_with_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ParametersOneTimeCommandExample::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create('ecotone.channel.doSomething', 'doSomething', [ConsoleCommandParameter::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name', false), ConsoleCommandParameter::create('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname', false)]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(ParametersOneTimeCommandExample::class, InterfaceToCall::create(ParametersOneTimeCommandExample::class, 'execute'))
                        ->withMethodParameterConverters([
                            HeaderBuilder::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name'),
                            HeaderBuilder::create('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname'),
                        ])
                        ->withEndpointId('ecotone.endpoint.doSomething')
                        ->withInputChannelName('ecotone.channel.doSomething')
                )
        );
    }

    public function test_creating_with_default_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                DefaultParametersOneTimeCommandExample::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create('ecotone.channel.doSomething', 'doSomething', [ConsoleCommandParameter::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name', false), ConsoleCommandParameter::createWithDefaultValue('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname', false, false, 'cash')]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(DefaultParametersOneTimeCommandExample::class, InterfaceToCall::create(DefaultParametersOneTimeCommandExample::class, 'execute'))
                        ->withMethodParameterConverters([
                            HeaderBuilder::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name'),
                            HeaderBuilder::create('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname'),
                        ])
                        ->withEndpointId('ecotone.endpoint.doSomething')
                        ->withInputChannelName('ecotone.channel.doSomething')
                )
        );
    }

    public function test_creating_with_reference_parameters_command()
    {
        $annotationConfiguration = ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                ParametersWithReferenceOneTimeCommandExample::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );

        $configuration = $this->createMessagingSystemConfiguration();
        $annotationConfiguration->prepare($configuration, [], ModuleReferenceSearchService::createEmpty(), InterfaceToCallRegistry::createEmpty());

        $this->assertEquals(
            $configuration,
            $this->createMessagingSystemConfiguration()
                ->registerConsoleCommand(ConsoleCommandConfiguration::create('ecotone.channel.doSomething', 'doSomething', [ConsoleCommandParameter::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name', false), ConsoleCommandParameter::create('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname', false)]))
                ->registerMessageHandler(
                    ServiceActivatorBuilder::create(ParametersWithReferenceOneTimeCommandExample::class, InterfaceToCall::create(ParametersWithReferenceOneTimeCommandExample::class, 'execute'))
                        ->withMethodParameterConverters([
                            HeaderBuilder::create('name', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'name'),
                            HeaderBuilder::create('surname', ConsoleCommandModule::ECOTONE_COMMAND_PARAMETER_PREFIX . 'surname'),
                            ReferenceBuilder::create('object', stdClass::class),
                        ])
                        ->withEndpointId('ecotone.endpoint.doSomething')
                        ->withInputChannelName('ecotone.channel.doSomething')
                )
        );
    }

    public function test_throwing_exception_when_one_time_command_having_incorrect_return_type()
    {
        $this->expectException(InvalidArgumentException::class);

        ConsoleCommandModule::create(
            InMemoryAnnotationFinder::createFrom([
                OneTimeWithIncorrectResultSet::class,
            ]),
            InterfaceToCallRegistry::createEmpty()
        );
    }

    public function test_execute_console_command_with_array_of_options()
    {
        $consoleCommand = new ConsoleCommandWithArrayOptions();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithArrayOptions::class],
            [$consoleCommand]
        );

        $ecotoneLite->runConsoleCommand('cli-command:array-options', [
            'names' => ['one', 'two'],
        ]);

        $this->assertEquals(
            [['one', 'two']],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_command_with_array_of_options_and_argument()
    {
        $consoleCommand = new ConsoleCommandWithArrayOptions();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithArrayOptions::class],
            [$consoleCommand]
        );

        $ecotoneLite->runConsoleCommand('cli-command:array-options-and-argument', [
            'email' => 'test@example.com',
            'names' => ['one', 'two'],
        ]);

        $this->assertEquals(
            ['test@example.com', ['one', 'two']],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_extra_header_values()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $ecotoneLite->runConsoleCommand('cli-command:with-headers', [
            'content' => 'Hello World',
            'header' => ['email:test@example.com'],
        ]);

        $this->assertEquals(
            ['Hello World', 'test@example.com'],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_multiple_extra_header_values()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $ecotoneLite->runConsoleCommand('cli-command:with-multiple-headers', [
            'content' => 'Hello World',
            'header' => ['supportive_email:test@example.com', 'billing_email:test2@example.com'],
        ]);

        $this->assertEquals(
            ['Hello World', 'test@example.com', 'test2@example.com'],
            $consoleCommand->getParameters()
        );
    }

    public function test_execute_console_with_incorrect_header_value()
    {
        $consoleCommand = new ConsoleCommandWithMessageHeaders();
        $stdClassConverter = new StdClassConverter();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ConsoleCommandWithMessageHeaders::class, StdClassConverter::class],
            [$consoleCommand, $stdClassConverter]
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotoneLite->runConsoleCommand('cli-command:with-headers', [
            'content' => 'Hello World',
            'header' => ['email'],
        ]);
    }
}
