<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Gateway;

use Test\Ecotone\Messaging\Fixture\Handler\Gateway\MultipleMethodsGatewayExample;
use Test\Ecotone\Messaging\Fixture\Service\ServiceInterface\ServiceInterfaceSendOnly;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayDefinition;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\CombinedGatewayBuilder;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * Class MultipleMethodGatewayBuilder
 * @package Test\Ecotone\Messaging\Unit\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CombinedGatewayBuilderTest extends TestCase
{
    /**
     * @throws MessagingException
     */
    public function test_proxy_to_specific_gateway()
    {
        $requestChannelNameGatewayOne = "requestChannel1";
        $requestChannelGatewayOne = QueueChannel::create();
        $gatewayProxyBuilderOne = GatewayProxyBuilder::create('ref-name', MultipleMethodsGatewayExample::class, 'execute1', $requestChannelNameGatewayOne);
        $requestChannelNameGatewayTwo = "requestChannel2";
        $requestChannelGatewayTwo = QueueChannel::create();
        $gatewayProxyBuilderTwo = GatewayProxyBuilder::create('ref-name', MultipleMethodsGatewayExample::class, 'execute2', $requestChannelNameGatewayTwo);

        $referenceSearchService = InMemoryReferenceSearchService::createEmpty();
        $channelResolver        = InMemoryChannelResolver::createFromAssociativeArray(
            [
                $requestChannelNameGatewayOne => $requestChannelGatewayOne,
                $requestChannelNameGatewayTwo => $requestChannelGatewayTwo
            ]
        );

        $multipleGatewayBuilder = CombinedGatewayBuilder::create(MultipleMethodsGatewayExample::class, ["execute1" => $gatewayProxyBuilderOne->buildWithoutProxyObject($referenceSearchService, $channelResolver), "execute2" => $gatewayProxyBuilderTwo->buildWithoutProxyObject($referenceSearchService, $channelResolver)]);
        /** @var MultipleMethodsGatewayExample $gateway */
        $gateway                = $multipleGatewayBuilder->build(
            $referenceSearchService,
            $channelResolver
        );

        $gateway->execute1('some1');
        $payload = $requestChannelGatewayOne->receive()->getPayload();
        $this->assertEquals($payload,'some1');
        $this->assertNull($requestChannelGatewayTwo->receive());

        $gateway->execute2('some2');
        $this->assertNull($requestChannelGatewayOne->receive());
        $this->assertEquals($requestChannelGatewayTwo->receive()->getPayload(),'some2');
    }
}