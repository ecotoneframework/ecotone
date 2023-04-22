<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Unit;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\CreateMerchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\Merchant;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\MerchantSubscriber;
use Test\Ecotone\Modelling\Fixture\CommandEventFlow\User;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestAbstractHandler;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestCommand;
use Test\Ecotone\Modelling\Fixture\HandlerWithAbstractClass\TestHandler;
use Test\Ecotone\Modelling\Fixture\Outbox\OutboxWithMultipleChannels;

/**
 * @internal
 */
final class ModellingEcotoneLiteTest extends TestCase
{
    public function test_command_event_command_flow()
    {
        $ecotoneTestSupport = EcotoneLite::bootstrapFlowTesting(
            [Merchant::class, User::class, MerchantSubscriber::class],
            [
                new MerchantSubscriber(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            allowGatewaysToBeRegisteredInContainer: true
        );

        $merchantId = '123';
        $this->assertTrue(
            $ecotoneTestSupport
                ->sendCommand(new CreateMerchant($merchantId))
                ->sendQueryWithRouting('user.get', metadata: ['aggregate.id' => $merchantId])
        );
    }

    public function test_calling_command_handler_with_abstract_class()
    {
        $ecotoneLite = EcotoneLite::bootstrapForTesting(
            [TestHandler::class, TestAbstractHandler::class],
            [
                new TestHandler(),
            ],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
        );

        $this->assertEquals(
            1,
            $ecotoneLite->getCommandBus()->send(new TestCommand(1))
        );
    }

    public function test_calling_asynchronous_command_handler_with_pass_through_message_channels()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OutboxWithMultipleChannels::class],
            [
                new OutboxWithMultipleChannels(),
            ],
            ServiceConfiguration::createWithAsynchronicityOnly()
        );

        $ecotoneLite->sendCommandWithRoutingKey('outboxWithMultipleChannels', 1);
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('outbox');
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('rabbitMQ');
        $this->assertEquals(
            1,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );
    }

    public function test_calling_asynchronous_command_handler_with_combined_message_channel()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OutboxWithMultipleChannels::class],
            [
                new OutboxWithMultipleChannels(),
            ],
            ServiceConfiguration::createWithAsynchronicityOnly()
        );

        $ecotoneLite->sendCommandWithRoutingKey('outboxWithCombinedChannels', 1);
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('outbox');
        $this->assertEquals(
            0,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );

        $ecotoneLite->run('rabbitMQ');
        $this->assertEquals(
            1,
            $ecotoneLite->sendQueryWithRouting('getResult')
        );
    }
}
