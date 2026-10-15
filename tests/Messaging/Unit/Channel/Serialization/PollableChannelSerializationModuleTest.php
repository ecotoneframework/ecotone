<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Channel\Serialization;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Lite\Test\FlowTestSupport;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Modelling\Fixture\Order\OrderService;
use Test\Ecotone\Modelling\Fixture\Order\PlaceOrder;

/**
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
final class PollableChannelSerializationModuleTest extends TestCase
{
    public function test_serializing_message_using_channel_serialization()
    {
        $ecotoneLite = $this->bootstrapEcotone(
            [OrderService::class],
            [new OrderService()],
            [
                SimpleMessageChannelBuilder::createQueueChannel(
                    'orders',
                    conversionMediaType: MediaType::createApplicationXPHPSerialized()->toString()
                ),
            ]
        );

        $ecotoneLite->sendCommand(new PlaceOrder('1'));

        $this->assertSame(
            addslashes(serialize(new PlaceOrder('1'))),
            $ecotoneLite->getMessageChannel('orders')->receive()->getPayload()
        );
    }

    /**
     * @param string[] $classesToResolve
     * @param object[] $services
     * @param MessageChannelBuilder[] $channelBuilders
     * @param object[] $extensionObjects
     */
    private function bootstrapEcotone(array $classesToResolve, array $services, array $channelBuilders, array $extensionObjects = []): FlowTestSupport
    {
        return EcotoneLite::bootstrapFlowTesting(
            $classesToResolve,
            $services,
            (ServiceConfiguration::createWithDefaults()
                ->withExtensionObjects($extensionObjects))->withExtensionObjects($channelBuilders)
                ->withEnabledModulePackageNames([])
        );
    }
}
