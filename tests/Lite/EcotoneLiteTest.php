<?php

namespace Test\Ecotone\Lite;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ConsoleCommandResultSet;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Order\ChannelConfiguration;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class EcotoneLiteTest extends TestCase
{
    public function test_it_can_run_console_command(): void
    {
        $ecotone = EcotoneLite::bootstrap(
            [OrderService::class, ChannelConfiguration::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withEnvironment('test')
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('orders'))
        );

        $ecotone->runConsoleCommand('ecotone:list', []);
        $this->expectNotToPerformAssertions();
    }

    public function test_flow_testing_refuses_test_configuration_passed_as_extension_object(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EcotoneLite::bootstrapFlowTesting() was given a TestConfiguration as an extension object of the ServiceConfiguration. A flow test runs with exactly one TestConfiguration, the one passed as the testConfiguration: parameter, so this one would be replaced by that one and its settings ignored. Remove it from withExtensionObjects() / addExtensionObject() and pass it as EcotoneLite::bootstrapFlowTesting(..., testConfiguration: $yourTestConfiguration) instead.');

        EcotoneLite::bootstrapFlowTesting(
            configuration: ServiceConfiguration::createWithDefaults()
                ->addExtensionObject(TestConfiguration::createWithDefaults()->withSpyOnChannel('async'))
                ->withEnabledModulePackageNames([]),
        );
    }

    public function test_with_spying_on_multiple_channels(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            testConfiguration: TestConfiguration::createWithDefaults()
                ->withSpyOnChannel('async1')
                ->withSpyOnChannel('async2'),
            configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async1'))->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async2'))
                ->withEnabledModulePackageNames([])
        );

        $ecotoneLite->sendDirectToChannel('async1', 'test1');
        $ecotoneLite->sendDirectToChannel('async2', 'test2');

        $this->assertEquals(
            [
                'test1',
            ],
            $ecotoneLite->popRecordedMessagePayloadsFrom('async1')
        );
        $this->assertEquals(
            [
                'test2',
            ],
            $ecotoneLite->popRecordedMessagePayloadsFrom('async2')
        );
    }

    public function test_handling_closure_configuration_variables(): void
    {
        $ecotone = EcotoneLite::bootstrap(
            [OrderService::class, ChannelConfiguration::class],
            [new OrderService()],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withEnvironment('test')
                ->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('orders')),
            configurationVariables: [
                'name' => fn () => 'Name',
            ]
        );

        $this->assertEquals(
            ConsoleCommandResultSet::create(['Name'], [['orders']]),
            $ecotone->runConsoleCommand('ecotone:list', [])
        );
    }

    public function test_not_overriding_pcntl_handler_during_tests(): void
    {
        if (! extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl extension is not loaded');
        }

        $ecotone = EcotoneLite::bootstrapFlowTesting(configuration: ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))->withEnabledModulePackageNames([]));

        $handler = pcntl_signal_get_handler(SIGINT);

        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(
            $handler,
            pcntl_signal_get_handler(SIGINT),
            'PCNTL signal handler was changed during test execution after running consumer'
        );
    }
}
