<?php

namespace Test\Ecotone\Lite;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            environment: 'test',
        );

        $ecotone->runConsoleCommand('ecotone:list', []);
        $this->expectNotToPerformAssertions();
    }

    public function test_flow_testing_refuses_test_configuration_passed_as_extension_object(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('EcotoneLite::bootstrapFlowTesting() was given a TestConfiguration in extensionObjects. A flow test runs with exactly one TestConfiguration, the one passed as the testConfiguration: parameter, so this one would be replaced by that one and its settings ignored. Remove it from extensionObjects and pass it as EcotoneLite::bootstrapFlowTesting(..., testConfiguration: $yourTestConfiguration) instead.');

        EcotoneLite::bootstrapFlowTesting(extensionObjects: [TestConfiguration::createWithDefaults()->withSpyOnChannel('async')], enabledModules: []);
    }

    public function test_with_spying_on_multiple_channels(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            extensionObjects: [
                SimpleMessageChannelBuilder::createQueueChannel('async1'),
                SimpleMessageChannelBuilder::createQueueChannel('async2'),
            ],
            enabledModules: [],
            testConfiguration: TestConfiguration::createWithDefaults()
            ->withSpyOnChannel('async1')
            ->withSpyOnChannel('async2'),
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
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('orders')],
            enabledModules: [],
            configurationVariables: [
                'name' => fn () => 'Name',
            ],
            environment: 'test',
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

        $ecotone = EcotoneLite::bootstrapFlowTesting(extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')], enabledModules: []);

        $handler = pcntl_signal_get_handler(SIGINT);

        $ecotone->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertSame(
            $handler,
            pcntl_signal_get_handler(SIGINT),
            'PCNTL signal handler was changed during test execution after running consumer'
        );
    }
}
