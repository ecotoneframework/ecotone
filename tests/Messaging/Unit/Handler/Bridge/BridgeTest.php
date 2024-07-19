<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Bridge;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\Endpoint\ExecutionPollingMetadata;
use Ecotone\Messaging\Support\MessageBuilder;
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
        $ecotoneLite = EcotoneLite::bootstrapForTesting(
            [BridgeExample::class],
            [new BridgeExample()]
        );

        $this->assertEquals(
            6,
            $ecotoneLite->sendMessage('bridgeExample', 1)
        );
    }

    public function test_intercepting_asynchronous_endpoint_should_happen_for_whole_asynchronous_processing()
    {
        $asynchronousBridgeExample = new AsynchronousBridgeExample();
        $ecotoneLite = EcotoneLite::bootstrapForTesting(
            [AsynchronousBridgeExample::class],
            [$asynchronousBridgeExample],
            ServiceConfiguration::createWithAsynchronicityOnly()
        );

        $ecotoneLite->sendMessage('bridgeExample', MessageBuilder::withPayload(1)->build());
        $ecotoneLite->run('async', ExecutionPollingMetadata::createWithTestingSetup());

        $this->assertEquals(
            30,
            $asynchronousBridgeExample->result
        );
    }
}
