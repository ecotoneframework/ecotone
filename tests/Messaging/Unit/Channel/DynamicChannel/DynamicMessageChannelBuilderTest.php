<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\DynamicChannel;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Channel\DynamicChannel\DynamicMessageChannelBuilder;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Support\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Channel\DynamicChannel\DynamicChannelResolver;
use Test\Ecotone\Messaging\Fixture\Handler\SuccessServiceActivator;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class DynamicMessageChannelBuilderTest extends TestCase
{
    public function test_sending_and_receiving_from_single_channel(): void
    {
        $successServiceActivator = new SuccessServiceActivator();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [$successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel', ['channel_one']),
                SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
        $ecotoneLite->run('async_channel');
        $this->assertSame(2, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_and_receiving_from_multiple_channels(): void
    {
        $successServiceActivator = new SuccessServiceActivator();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [$successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobinWithDifferentChannels('async_channel', sendingChannelNames: ['channel_one'], receivingChannelNames: ['channel_two', 'channel_one']),
                SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
                SimpleMessageChannelBuilder::createQueueChannel('channel_two'),
            ]
        );

        /** Send to channel_one */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** We are fetching with channel_two */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** We are fetching with channel_one */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_and_receiving_from_internal_channels(): void
    {
        $successServiceActivator = new SuccessServiceActivator();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [$successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobinWithDifferentChannels(
                    'async_channel',
                    sendingChannelNames: ['channel_one'],
                    receivingChannelNames: ['channel_two', 'channel_one'],
                )
                    ->withInternalChannels([
                        SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
                        SimpleMessageChannelBuilder::createQueueChannel('channel_two'),
                    ]),
            ]
        );

        /** Send to channel_one */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** We are fetching with channel_two */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** We are fetching with channel_one */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_and_receiving_from_internal_channels_with_custom_name(): void
    {
        $successServiceActivator = new SuccessServiceActivator();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [SuccessServiceActivator::class],
            [$successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobinWithDifferentChannels(
                    'async_channel',
                    sendingChannelNames: ['x'],
                    receivingChannelNames: ['x', 'y'],
                )
                    ->withInternalChannels([
                        'x' => SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
                        'y' => SimpleMessageChannelBuilder::createQueueChannel('channel_two'),
                    ]),
            ]
        );

        /** Send to x */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
        /** Send to y */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** We are fetching with x */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** We are fetching with y */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** We are fetching with x */
        $ecotoneLite->run('async_channel');
        $this->assertSame(2, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_and_receiving_from_single_channel_using_custom_strategy(): void
    {
        $dynamicChannelResolver = new DynamicChannelResolver(
            ['channel_one'],
            ['channel_one']
        );
        $successServiceActivator = new SuccessServiceActivator();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [$dynamicChannelResolver, $successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel')
                    ->withCustomSendingStrategy('dynamicChannel.send')
                    ->withCustomReceivingStrategy('dynamicChannel.receive'),
                SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Sending to null channel */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
        /** Receiving from null channel */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_and_receiving_from_multiple_channels_using_custom_strategy(): void
    {
        $dynamicChannelResolver = new DynamicChannelResolver(
            ['channel_one', 'channel_two'],
            ['channel_two', 'channel_one', 'channel_three', 'channel_two']
        );
        $successServiceActivator = new SuccessServiceActivator();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [$dynamicChannelResolver, $successServiceActivator],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel')
                    ->withCustomSendingStrategy('dynamicChannel.send')
                    ->withCustomReceivingStrategy('dynamicChannel.receive'),
                SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
                SimpleMessageChannelBuilder::createQueueChannel('channel_two'),
                SimpleMessageChannelBuilder::createQueueChannel('channel_three'),
            ]
        );

        /** Sending to channel one */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** Receiving from channel two */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from channel one */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Sending to channel two */
        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** Receiving from channel three */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from channel two */
        $ecotoneLite->run('async_channel');
        $this->assertSame(2, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_using_header_strategy_with_mapping(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel', ['channel_one', 'channel_two', 'channel_three'])
                    ->withHeaderSendingStrategy(
                        'tenant',
                        [
                            'tenant_a' => 'channel_one',
                            'tenant_b' => 'channel_two',
                            'tenant_c' => 'channel_three',
                        ]
                    )
                    ->withInternalChannels([
                        SimpleMessageChannelBuilder::createQueueChannel('channel_one'),
                        SimpleMessageChannelBuilder::createQueueChannel('channel_two'),
                        SimpleMessageChannelBuilder::createQueueChannel('channel_three'),
                    ]),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test'], ['tenant' => 'tenant_b']);

        /** Receiving from tenant_a */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from tenant_b */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test'], ['tenant' => 'tenant_a']);

        /** Receiving from tenant_c */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from tenant_a */
        $ecotoneLite->run('async_channel');
        $this->assertSame(2, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_using_header_strategy_without_mapping(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel', ['tenant_a', 'tenant_b', 'tenant_c'])
                    ->withHeaderSendingStrategy(
                        'tenant',
                    )
                    ->withInternalChannels([
                        SimpleMessageChannelBuilder::createQueueChannel('tenant_a'),
                        SimpleMessageChannelBuilder::createQueueChannel('tenant_b'),
                        SimpleMessageChannelBuilder::createQueueChannel('tenant_c'),
                    ]),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test'], ['tenant' => 'tenant_b']);

        /** Receiving from tenant_a */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from tenant_b */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test'], ['tenant' => 'tenant_a']);

        /** Receiving from tenant_c */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from tenant_a */
        $ecotoneLite->run('async_channel');
        $this->assertSame(2, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }

    public function test_sending_using_header_strategy_throws_exception_when_header_is_missing(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel', ['tenant_a', 'tenant_b', 'tenant_c'])
                    ->withHeaderSendingStrategy(
                        'tenant',
                    ),
            ]
        );

        $this->expectException(InvalidArgumentException::class);

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);
    }

    public function test_sending_using_header_strategy_with_default_channel(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DynamicChannelResolver::class, SuccessServiceActivator::class],
            [new SuccessServiceActivator()],
            ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects([
                    PollingMetadata::create('async_channel')
                        ->setExecutionAmountLimit(1),
                ]),
            enableAsynchronousProcessing: [
                DynamicMessageChannelBuilder::createRoundRobin('async_channel', ['tenant_a', 'tenant_shared'])
                    ->withHeaderSendingStrategy(
                        'tenant',
                        defaultChannelName: 'tenant_shared'
                    )
                    ->withInternalChannels([
                        SimpleMessageChannelBuilder::createQueueChannel('tenant_a'),
                        SimpleMessageChannelBuilder::createQueueChannel('tenant_shared'),
                    ]),
            ]
        );

        $ecotoneLite->sendDirectToChannel('handle_channel', ['test']);

        /** Receiving from tenant_a */
        $ecotoneLite->run('async_channel');
        $this->assertSame(0, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));

        /** Receiving from tenant_shared */
        $ecotoneLite->run('async_channel');
        $this->assertSame(1, $ecotoneLite->sendQueryWithRouting('get_number_of_calls'));
    }
}
