<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\Collector;

use Ecotone\Api\ExtensionObject\DynamicMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Channel\Collector\CollectedMessage;
use Ecotone\Messaging\Channel\Collector\Config\CollectorConfiguration;
use Ecotone\Messaging\Channel\ExceptionalQueueChannel;
use Ecotone\Messaging\Channel\PollableChannel\GlobalPollableChannelConfiguration;
use Ecotone\Messaging\Channel\PollableChannel\PollableChannelConfiguration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Conversion\ConversionException;
use Ecotone\Test\ChannelTestSupport;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function str_contains;

use Test\Ecotone\Messaging\Fixture\Channel\DynamicChannel\DynamicChannelResolver;
use Test\Ecotone\Modelling\Fixture\Collector\BetNotificator;
use Test\Ecotone\Modelling\Fixture\Collector\BetService;
use Test\Ecotone\Modelling\Fixture\Collector\BetStatistics;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class CollectorModuleTest extends TestCase
{
    public function test_receiving_collected_message_from_command_handler_without_exception()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class],
            [new OrderService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('orders', conversionMediaType: MediaType::createApplicationXPHP()),
            ],
            [PollableChannelConfiguration::neverRetry('orders')->withCollector(true)]
        );

        $command = new PlaceOrder('1');
        $ecotoneLite->sendCommand($command);

        $this->assertCount(0, $ecotoneLite->sendQueryWithRouting('order.getOrders'));
        $this->assertEquals($command, ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->receive()->getPayload());
    }

    public function test_collected_messages_are_not_duplicated_when_using_round_robin()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class],
            [new OrderService()],
            [
                DynamicMessageChannelBuilder::createRoundRobin('orders', ['orders_priority']),
                SimpleMessageChannelBuilder::createQueueChannel('orders_priority', conversionMediaType: MediaType::createApplicationXPHP()),
            ],
            [
                PollableChannelConfiguration::neverRetry('orders')->withCollector(true),
                PollableChannelConfiguration::neverRetry('orders_priority')->withCollector(true),
            ],
            true
        );

        $command = new PlaceOrder('1');
        $ecotoneLite->sendCommand($command);

        $this->assertCount(0, $ecotoneLite->sendQueryWithRouting('order.getOrders'));
        $this->assertEquals($command, ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->receive()->getPayload());
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->receive());
    }

    public function test_collected_messages_are_not_duplicated_when_using_dynamic_channel()
    {
        $dynamicChannelResolver = new DynamicChannelResolver(
            ['orders_priority'],
            ['orders_priority']
        );

        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class, DynamicChannelResolver::class],
            [new OrderService(), $dynamicChannelResolver],
            [
                DynamicMessageChannelBuilder::createRoundRobin('orders')
                    ->withCustomSendingStrategy('dynamicChannel.send')
                    ->withCustomReceivingStrategy('dynamicChannel.receive'),
                SimpleMessageChannelBuilder::createQueueChannel('orders_priority', conversionMediaType: MediaType::createApplicationXPHP()),
            ],
            [
                PollableChannelConfiguration::neverRetry('orders')->withCollector(true),
                PollableChannelConfiguration::neverRetry('orders_priority')->withCollector(true),
            ],
            true
        );

        $command = new PlaceOrder('1');
        $ecotoneLite->sendCommand($command);

        $this->assertCount(0, $ecotoneLite->sendQueryWithRouting('order.getOrders'));
        $this->assertEquals($command, ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->receive()->getPayload());
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('orders')->receive());
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
            $ecotoneLite->sendCommandWithRouting('makeBet', true);
        } catch (RuntimeException) {
        }

        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRouting('makeBet', false);
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
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
            $ecotoneLite->sendCommandWithRouting('makeBet', true);
        } catch (RuntimeException) {
        }

        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');
    }

    public function test_collected_message_are_not_sent_when_handler_exception_happens_in_async_scenario()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
                SimpleMessageChannelBuilder::createQueueChannel('customErrorChannel'),
            ],
            [
                PollableChannelConfiguration::neverRetry('bets')->withCollector(true),
                PollingMetadata::create('bets')->withTestingSetup(handledMessageLimit: 1, stopOnError: false)->withErrorChannelName('customErrorChannel'),
            ]
        );

        $ecotoneLite->sendCommandWithRouting('asyncMakeBet', true);
        $ecotoneLite->run('bets');
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('customErrorChannel')->receive(), 'Message should be sent due to error channel');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No message should not be sent due to exception');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRouting('asyncMakeBet', false);
        $ecotoneLite->run('bets');
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
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
            $ecotoneLite->sendCommandWithRouting('makeBet', true);
        } catch (RuntimeException) {
        }

        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRouting('makeBet', false);
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_not_collected_message_will_be_sent_to_channel_before_exception_with_global_configuration()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class],
            [new BetService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
            ],
            [GlobalPollableChannelConfiguration::neverRetry()->withCollector(false)]
        );

        try {
            $ecotoneLite->sendCommandWithRouting('makeBet', true);
        } catch (RuntimeException) {
        }

        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRouting('makeBet', false);
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
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

        $ecotoneLite->sendCommandWithRouting('makeBet', false);

        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('notifications')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('notifications')->receive(), 'No more messages should be collected');
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
            $ecotoneLite->sendCommandWithRouting('makeBlindBet', true);
        } catch (RuntimeException) {
        }

        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was collected');

        /** Previous messages should be cleared and not resent */
        $ecotoneLite->sendCommandWithRouting('makeBlindBet', false);
        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'No more messages should be collected');
    }

    public function test_throwing_exception_if_multiple_collectors_registered_for_same_channel()
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

        $ecotoneLite->sendCommandWithRouting('makeBet', false);

        $this->assertNotNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was not collected');
    }

    public function test_failure_during_serialization_of_given_message_should_result_in_not_sending_any_message()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [BetService::class, BetNotificator::class, BetStatistics::class],
            [new BetService(), new BetNotificator(), new BetStatistics()],
            [
                SimpleMessageChannelBuilder::createQueueChannel('bets'),
                /** Lack of media type conversion for this */
                SimpleMessageChannelBuilder::createQueueChannel('notifications', conversionMediaType: MediaType::createApplicationJson()),
                SimpleMessageChannelBuilder::createQueueChannel('statistics'),
            ],
            [
                PollableChannelConfiguration::createWithDefaults('bets')->withCollector(true),
                PollableChannelConfiguration::createWithDefaults('notifications')->withCollector(true),
                PollableChannelConfiguration::createWithDefaults('statistics')->withCollector(true),
            ]
        );

        try {
            $ecotoneLite
                ->sendCommandWithRouting('makeBet', false);
        } catch (ConversionException) {
        }

        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('bets')->receive(), 'Message was sent');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('notifications')->receive(), 'Message was sent');
        $this->assertNull(ChannelTestSupport::of($ecotoneLite)->getMessageChannel('statistics')->receive(), 'Message was sent');
    }

    /**
     * @param string[] $classesToResolve
     * @param object[] $services
     * @param MessageChannelBuilder[] $channelBuilders
     * @param CollectorConfiguration[] $collectorConfigurations
     */
    private function bootstrapEcotone(array $classesToResolve, array $services, array $channelBuilders, array $collectorConfigurations, bool $withEnterpriseLicence = false): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            extensionObjects: array_merge($collectorConfigurations, $channelBuilders),
            enabledModules: [],
            licenceKey: $withEnterpriseLicence ? LicenceTesting::VALID_LICENCE : null,
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
