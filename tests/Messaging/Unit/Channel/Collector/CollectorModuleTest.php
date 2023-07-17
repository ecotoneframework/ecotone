<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\Collector;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\FlowTestSupport;
use Ecotone\Messaging\Channel\Collector\CollectedMessage;
use Ecotone\Messaging\Channel\Collector\Config\CollectorConfiguration;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\MessageChannelBuilder;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\MessageHeaders;
use PHPUnit\Framework\TestCase;

use function str_contains;

use Test\Ecotone\Modelling\Fixture\Collector\BetNotificator;
use Test\Ecotone\Modelling\Fixture\Collector\BetService;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

/**
 * @internal
 */
final class CollectorModuleTest extends TestCase
{
    public function test_receiving_collected_message_from_command_handler()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class],
            [new OrderService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('orders'),
            ],
            [PollableChannelConfiguration::neverRetry('orders')->withCollector(true)]
        );

        $command = new PlaceOrder('1');
        $ecotoneLite->sendCommand($command);

        $this->assertCount(0, $ecotoneLite->sendQueryWithRouting('order.getOrders'));
        $this->assertEquals($command, $ecotoneLite->getMessageChannel('orders')->receive()->getPayload());
    }

    public function test_collected_message_is_delayed_so_messages_are_not_sent_on_handler_exception()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [PollableChannelConfiguration::neverRetry('bets')->withCollector(true)]
        );

        try {
            $ecotoneLite->sendCommandWithRoutingKey('makeBet', true);
        } catch (\RuntimeException) {
        }

        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRoutingKey('makeBet', false);
        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_collected_message_is_delayed_so_messages_are_not_sent_on_handler_exception_with_default_configuration()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [] // no config needed
        );

        try {
            $ecotoneLite->sendCommandWithRoutingKey('makeBet', true);
        } catch (\RuntimeException) {
        }

        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');
    }

    public function test_collected_message_is_delayed_so_messages_are_not_sent_on_handler_exception_when_async_scenario()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [PollableChannelConfiguration::neverRetry('bets')->withCollector(true)]
        );

        $ecotoneLite->sendCommandWithRoutingKey('asyncMakeBet', true);
        try {
            $ecotoneLite->run('bets', ExecutionPollingMetadata::createWithTestingSetup());
        } catch (\RuntimeException) {
        }
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRoutingKey('asyncMakeBet', false);
        $ecotoneLite->run('bets', ExecutionPollingMetadata::createWithTestingSetup());
        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_not_collected_message_will_be_sent_to_channel_before_exception()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [PollableChannelConfiguration::neverRetry('bets')->withCollector(false)]
        );

        try {
            $ecotoneLite->sendCommandWithRoutingKey('makeBet', true);
        } catch (\RuntimeException) {
        }

        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRoutingKey('makeBet', false);
        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_collecting_messages_from_different_channels()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class, BetNotificator::class],
            [new BetService(), new BetNotificator()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
                SimpleMessageChannelBuilder::createQueueChannel('notifications'),
            ],
            [
                PollableChannelConfiguration::neverRetry('bets')->withCollector(true),
                PollableChannelConfiguration::neverRetry('notifications')->withCollector(true),
            ]
        );

        $ecotoneLite->sendCommandWithRoutingKey('makeBet', false);

        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No more messages should be collected');
        $this->assertNotNull($ecotoneLite->getMessageChannel('notifications')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('notifications')->receive(), 'No more messages should be collected');
    }

    public function test_when_command_bus_inside_command_bus_it_will_not_release_messages_to_early()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [PollableChannelConfiguration::neverRetry('bets')->withCollector(true)]
        );

        try {
            $ecotoneLite->sendCommandWithRoutingKey('makeBlindBet', true);
        } catch (\RuntimeException) {
        }

        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was collected');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRoutingKey('makeBlindBet', false);
        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull($ecotoneLite->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_throwing_exception_if_multiple_collector_registered_for_same_channel()
    {
        $this->expectException(ConfigurationException::class);

        $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [
                PollableChannelConfiguration::neverRetry('bets')->withCollector(true),
                PollableChannelConfiguration::neverRetry('bets')->withCollector(true),
            ]
        );
    }

    public function test_failure_while_sending_to_collect_use_retry_strategy()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                ExceptionalQueueChannel::createWithExceptionOnSend('bets', 1),
            ],
            [PollableChannelConfiguration::createWithDefaults('bets')->withCollector(true)]
        );

        $ecotoneLite->sendCommandWithRoutingKey('makeBet', false);

        $this->assertNotNull($ecotoneLite->getMessageChannel('bets')->receive(), 'Message was not collected');
    }

    /**
     * @param string[] $classesToResolve
     * @param object[] $services
     * @param MessageChannelBuilder[] $channelBuilders
     * @param CollectorConfiguration[] $collectorConfigurations
     */
    private function bootstrapEcotone(array $classesToResolve, array $services, array $channelBuilders, array $collectorConfigurations): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects($collectorConfigurations),
            enableAsynchronousProcessing: $channelBuilders
        );
    }

    /**
     * @param CollectedMessage[] $collectedMessages
     */
    private function containsMessageFor(array $collectedMessages, string $channelName, object $payload): bool
    {
        foreach ($collectedMessages as $collectedMessage) {
            if ($collectedMessage->getChannelName() === $channelName && $collectedMessage->getMessage()->getPayload() == $payload) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param CollectedMessage[] $collectedMessages
     */
    private function containsMessageWithRoutingKeyFor(array $collectedMessages, string $channelName, object $payload, string $routingKey): bool
    {
        foreach ($collectedMessages as $collectedMessage) {
            if ($collectedMessage->getChannelName() === $channelName && $collectedMessage->getMessage()->getPayload() == $payload && str_contains($collectedMessage->getMessage()->getHeaders()->get(MessageHeaders::ROUTING_SLIP), $routingKey)) {
                return true;
            }
        }

        return false;
    }
}
