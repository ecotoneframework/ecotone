<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Handler\Gateway;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class GatewayInterceptorBuilder
 * @package SimplyCodedSoftware\Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayInterceptorBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilder
{
    /**
     * @var string
     */
    private $requestChannelName;

    /**
     * GatewayInterceptorBuilder constructor.
     * @param string $requestChannelName
     */
    private function __construct(string $requestChannelName)
    {
        $this->requestChannelName = $requestChannelName;
    }

    /**
     * @param string $requestChannelName
     * @return GatewayInterceptorBuilder
     */
    public static function create(string $requestChannelName) : self
    {
        return new self($requestChannelName);
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $gateway = GatewayProxyBuilder::create(Uuid::uuid4()->toString(), PassThroughGateway::class, "execute", $this->requestChannelName);

        return ServiceActivatorBuilder::createWithDirectReference($gateway, "execute")
                ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {

    }
}