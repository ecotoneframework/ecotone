<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\AsyncCommandBus;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\AsyncTicketCreator;
use Test\Ecotone\Messaging\Fixture\Service\Gateway\TicketService;

/**
 * licence Enterprise
 * @internal
 */
final class AsynchronousGatewayTest extends TestCase
{
    public function test_running_async_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AsyncTicketCreator::class, TicketService::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        /** @var AsyncTicketCreator $ticketCreator */
        $ticketCreator = $ecotoneLite->getGateway(AsyncTicketCreator::class);

        $ticketCreator->create('some');

        $this->assertEquals(
            [],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertEquals(
            ['some'],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );
    }

    public function test_running_async_gateway_inside_async_gateway(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AsyncTicketCreator::class, TicketService::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        /** @var AsyncTicketCreator $ticketCreator */
        $ticketCreator = $ecotoneLite->getGateway(AsyncTicketCreator::class);

        $ticketCreator->proxy('some');
        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('getTickets'));

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup()->withExecutionAmountLimit(1));
        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('getTickets'));

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertEquals(
            ['some'],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );
    }

    public function test_extending_command_bus_with_async_functionality(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AsyncCommandBus::class, TicketService::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        /** @var AsyncCommandBus $commandBus */
        $commandBus = $ecotoneLite->getGateway(AsyncCommandBus::class);

        $commandBus->sendWithRouting('createViaCommand', 'some');
        $this->assertEquals([], $ecotoneLite->sendQueryWithRouting('getTickets'));

        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup()->withExecutionAmountLimit(1));
        $this->assertEquals(
            ['some'],
            $ecotoneLite->sendQueryWithRouting('getTickets')
        );
    }

    public function test_throwing_exception_when_using_async_gateway_in_non_enterprise_mode(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [AsyncTicketCreator::class, TicketService::class],
            [new TicketService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );
    }
}
