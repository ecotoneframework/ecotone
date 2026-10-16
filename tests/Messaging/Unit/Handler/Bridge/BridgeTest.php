<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Bridge;

use Ecotone\Api\ExtensionObject\ExecutionPollingMetadata;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Test\ChannelTestSupport;
use Ecotone\Test\StaticMessageBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\InterceptedBridge\AsynchronousBridgeExample;
use Test\Ecotone\Messaging\Fixture\InterceptedBridge\BridgeExample;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class BridgeTest extends TestCase
{
    public function test_intercepting_message_handler_should_happen_only_for_given_endpoint()
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [BridgeExample::class],
            [new BridgeExample()],
            enabledModules: [],
        );

        $this->assertEquals(
            4,
            ChannelTestSupport::of($ecotoneLite)->sendMessageDirectToChannel('bridgeExample', StaticMessageBuilder::withPayload(1)->build())
        );
    }

    public function test_intercepting_asynchronous_endpoint_should_happen_for_whole_asynchronous_processing()
    {
        $asynchronousBridgeExample = new AsynchronousBridgeExample();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [AsynchronousBridgeExample::class],
            [$asynchronousBridgeExample],
            enabledModules: [],
        );

        ChannelTestSupport::of($ecotoneLite)->sendMessageDirectToChannel('bridgeExample', StaticMessageBuilder::withPayload(1)->build());
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertEquals(
            30,
            $asynchronousBridgeExample->result
        );
    }
}
