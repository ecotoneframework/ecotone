<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Orchestrator;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\AsynchronousOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\AuthorizationOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\CombinedOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\AsynchronousEventHandlerAuthorizationProcessor;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\AuthorizationProcess;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\AuthorizationProcessGateway;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\AuthorizationStarted;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\CommandHandlerAuthorizationProcessor;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\Incorrect\OrchestratorGatewayWithIncorrectMetadata;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Execution\Incorrect\OrchestratorGatewayWithIncorrectRouting;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\ArrayWithNonStringOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\InvalidReturnTypeOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\NoReturnTypeOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\NullableArrayOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\StringReturnTypeOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\UnionTypeOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\VoidReturnTypeOrchestrator;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\OrchestratorEndingDuringFlow;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\OrchestratorWithAsynchronousAndInputOutputChannels;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\OrchestratorWithAsynchronousStep;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\OrchestratorWithInternalBus;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\SimpleOrchestrator;

/**
 * licence Enterprise
 * @internal
 */
class OrchestratorTest extends TestCase
{
    public function test_orchestrator_passes_message(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class],
            [$orchestrator = new AuthorizationOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $result = $ecotoneLite->sendDirectToChannel('start.authorization', 'test-data');

        $this->assertNotNull($result);
        $this->assertEquals('email sent for: processed: validated: test-data', $result);

        $executedSteps = $orchestrator->getExecutedSteps();
        $this->assertEquals(['validate', 'process', 'sendEmail'], $executedSteps);
    }

    /**
     * Provides clear interface for executing workflow
     */
    public function test_orchestrator_executed_from_business_interface(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, AuthorizationProcess::class],
            [$orchestrator = new AuthorizationOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        /** @var AuthorizationProcess $authorizationProcess */
        $authorizationProcess = $ecotoneLite->getGateway(AuthorizationProcess::class);
        $result = $authorizationProcess->start('test-data');

        $this->assertNotNull($result);
        $this->assertEquals('email sent for: processed: validated: test-data', $result);

        $executedSteps = $orchestrator->getExecutedSteps();
        $this->assertEquals(['validate', 'process', 'sendEmail'], $executedSteps);
    }

    /**
     * Allows to execute workflow as a result of given Command
     */
    public function test_orchestrator_executed_from_command_handler_output_channel(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, CommandHandlerAuthorizationProcessor::class],
            [$orchestrator = new AuthorizationOrchestrator(), new CommandHandlerAuthorizationProcessor()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        /** @var CommandBus $commandBus */
        $commandBus = $ecotoneLite->getGateway(CommandBus::class);
        $result = $commandBus->sendWithRouting('execute.authorization', 'test-data');

        $this->assertNotNull($result);
        $this->assertEquals('email sent for: processed: validated: test-data', $result);

        $executedSteps = $orchestrator->getExecutedSteps();
        $this->assertEquals(['validate', 'process', 'sendEmail'], $executedSteps);
    }

    /**
     * Allows to execute workflow as a result of given Event happening
     */
    public function test_orchestrator_executed_from_event_handler_output_channel(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, AsynchronousEventHandlerAuthorizationProcessor::class],
            [$orchestrator = new AuthorizationOrchestrator(), new AsynchronousEventHandlerAuthorizationProcessor()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        /** @var EventBus $eventBus */
        $eventBus = $ecotoneLite->getGateway(EventBus::class);
        $eventBus->publish(new AuthorizationStarted('test-data'));
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $executedSteps = $orchestrator->getExecutedSteps();
        $this->assertEquals(['validate', 'process', 'sendEmail'], $executedSteps);
    }

    /**
     * Provides ability to dynamically pass routed channels
     */
    public function test_executing_orchestrator_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, AuthorizationProcessGateway::class],
            [$orchestrator = new AuthorizationOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        /** @var AuthorizationProcessGateway $gateway */
        $gateway = $ecotoneLite->getGateway(AuthorizationProcessGateway::class);
        $gateway->start([
            'validate',
            'process',
            'sendEmail',
        ], 'test-data', []);

        $executedSteps = $orchestrator->getExecutedSteps();
        $this->assertEquals(['validate', 'process', 'sendEmail'], $executedSteps);
    }

    public function test_routing_fails_if_provided_non_string_collection_for_orchestrator_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, AuthorizationProcessGateway::class],
            [$orchestrator = new AuthorizationOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        /** @var AuthorizationProcessGateway $gateway */
        $gateway = $ecotoneLite->getGateway(AuthorizationProcessGateway::class);
        $gateway->start([
            new stdClass(),
        ], 'test-data', []);
    }

    public function test_orchestrator_gateway_fails_with_incorrect_routing_channels(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [OrchestratorGatewayWithIncorrectRouting::class],
            [],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );
    }

    public function test_orchestrator_gateway_fails_with_incorrect_metadata(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [OrchestratorGatewayWithIncorrectMetadata::class],
            [],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );
    }

    public function test_orchestrator_returns_empty_array_no_routing_happens(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SimpleOrchestrator::class],
            [new SimpleOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $this->assertSame('test-data', $ecotoneLite->sendDirectToChannel('empty.workflow', 'test-data'));
    }

    public function test_orchestrator_with_single_step(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SimpleOrchestrator::class],
            [new SimpleOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $this->assertSame('validated: test-data', $ecotoneLite->sendDirectToChannel('single.step', 'test-data'));
    }

    public function test_throwing_exception_with_single_step_as_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\StringReturnTypeOrchestrator::singleStepAsString must return array of strings, but returns string');

        EcotoneLite::bootstrapFlowTesting(
            [StringReturnTypeOrchestrator::class],
            [new StringReturnTypeOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_throwing_exception_when_orchestrator_returns_non_array_or_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\InvalidReturnTypeOrchestrator::invalidReturnType must return array of strings, but returns stdClass');

        EcotoneLite::bootstrapFlowTesting(
            [InvalidReturnTypeOrchestrator::class],
            [new InvalidReturnTypeOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_throwing_exception_when_orchestrator_has_no_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\NoReturnTypeOrchestrator::noReturnType must return array of strings, but returns nullable type anything');

        EcotoneLite::bootstrapFlowTesting(
            [NoReturnTypeOrchestrator::class],
            [new NoReturnTypeOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_throwing_exception_when_orchestrator_has_union_type_with_array_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\UnionTypeOrchestrator::unionType must return array of strings, but returns union type array|string');

        EcotoneLite::bootstrapFlowTesting(
            [UnionTypeOrchestrator::class],
            [new UnionTypeOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_throwing_exception_when_orchestrator_has_nullable_array_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\NullableArrayOrchestrator::nullableArray must return array of strings, but returns nullable type array');

        EcotoneLite::bootstrapFlowTesting(
            [NullableArrayOrchestrator::class],
            [new NullableArrayOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_throwing_exception_with_array_returned_of_non_string(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator returned array must contain only strings');

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ArrayWithNonStringOrchestrator::class],
            [new ArrayWithNonStringOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        // This should fail at runtime when the orchestrator executes and returns non-string array
        $ecotoneLite->sendDirectToChannel('array.with.non.string', 'test-data');
    }

    public function test_throwing_exception_with_void_return_type(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Orchestrator method Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\Orchestrator\Incorrect\VoidReturnTypeOrchestrator::voidReturnType must return array of strings, but returns void');

        EcotoneLite::bootstrapFlowTesting(
            [VoidReturnTypeOrchestrator::class],
            [new VoidReturnTypeOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );
    }

    public function test_workflow_is_ending_on_null_returned_within_step(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrchestratorEndingDuringFlow::class],
            [$service = new OrchestratorEndingDuringFlow()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $ecotoneLite->sendDirectToChannel('orchestrator.ending.during.flow', 'test-data');

        $this->assertEquals(['step1', 'step2'], $service->getExecutedSteps());
    }

    public function test_second_orchestrator_is_step_within_the_workflow(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [CombinedOrchestrator::class],
            [$service = new CombinedOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $ecotoneLite->sendDirectToChannel('orchestrator.ending.during.flow', []);

        $this->assertEquals(['stepA', 'stepB', 'stepA', 'stepB', 'stepC'], $service->getExecutedSteps());
    }

    public function test_command_bus_is_called_within_the_workflow_not_affecting_orchestrator(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrchestratorWithInternalBus::class],
            [$service = new OrchestratorWithInternalBus()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE)
        );

        $ecotoneLite->sendDirectToChannel('orchestrator.ending.during.flow', []);

        $this->assertEquals(['stepA', 'stepB', 'commandBusAction.execute', 'stepC'], $service->getExecutedSteps());
    }

    public function test_asynchronous_orchestrator(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AsynchronousOrchestrator::class],
            [$service = new AsynchronousOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('asynchronous.workflow', []);

        $this->assertEquals([], $service->getExecutedSteps());

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertEquals(['stepA', 'stepB', 'stepC'], $service->getExecutedSteps());
    }

    public function test_asynchronous_step_within_orchestrator(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrchestratorWithAsynchronousStep::class],
            [$service = new OrchestratorWithAsynchronousStep()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('asynchronous.workflow', []);

        $this->assertEquals(['stepA'], $service->getExecutedSteps());

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertEquals(['stepA', 'stepB', 'stepC'], $service->getExecutedSteps());
    }

    public function test_asynchronous_step_within_orchestrator_with_input_output_channel(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrchestratorWithAsynchronousAndInputOutputChannels::class],
            [$service = new OrchestratorWithAsynchronousAndInputOutputChannels()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE]))
                ->withLicenceKey(LicenceTesting::VALID_LICENCE),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('asynchronous.workflow', []);

        $this->assertEquals(['stepA'], $service->getExecutedSteps());

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertEquals(['stepA', 'stepB'], $service->getExecutedSteps());

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertEquals(['stepA', 'stepB', 'stepD'], $service->getExecutedSteps());

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertEquals(['stepA', 'stepB', 'stepD', 'stepE', 'stepC'], $service->getExecutedSteps());
    }

    public function test_failing_on_using_orchestrator_gateway_without_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [AuthorizationOrchestrator::class, AuthorizationProcessGateway::class],
            [new AuthorizationOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE, ModulePackageList::ASYNCHRONOUS_PACKAGE])),
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ]
        );
    }

    public function test_orchestrator_requires_enterprise_license(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [SimpleOrchestrator::class],
            [new SimpleOrchestrator()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]))
            // No license key provided - should throw exception
        );
    }
}
