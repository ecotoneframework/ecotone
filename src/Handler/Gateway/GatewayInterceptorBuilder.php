<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway;
use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessageHandler;

/**
 * Class GatewayInterceptorBuilder
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway
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