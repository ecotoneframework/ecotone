<?php

namespace Test\Ecotone\Lite;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
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
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withEnvironment('test')
        );

        $ecotone->runConsoleCommand('ecotone:list', []);
        $this->expectNotToPerformAssertions();
    }

    public function test_with_spying_on_multiple_channels(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [],
            [],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async1'),
                SimpleMessageChannelBuilder::createQueueChannel('async2'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()
                ->withSpyOnChannel('async1')
                ->withSpyOnChannel('async2')
        );

        $ecotoneLite->sendDirectToChannel('async1', 'test1');
        $ecotoneLite->sendDirectToChannel('async2', 'test2');

        $this->assertEquals(
            [
                'test1',
            ],
            $ecotoneLite->getRecordedMessagePayloadsFrom('async1')
        );
        $this->assertEquals(
            [
                'test2',
            ],
            $ecotoneLite->getRecordedMessagePayloadsFrom('async2')
        );
    }
}
