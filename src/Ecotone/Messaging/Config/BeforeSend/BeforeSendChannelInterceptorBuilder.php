<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\BeforeSend;

use Ecotone\Messaging\Channel\ChannelInterceptor;
use Ecotone\Messaging\Channel\ChannelInterceptorBuilder;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ramsey\Uuid\Uuid;

class BeforeSendChannelInterceptorBuilder implements ChannelInterceptorBuilder
{
    private string $inputChannelName;
    private MethodInterceptor $methodInterceptor;
    private GatewayProxyBuilder $gateway;
    private string $internalRequestChannelName;

    public function __construct(string $inputChannelName, MethodInterceptor $methodInterceptor)
    {
        $this->inputChannelName  = $inputChannelName;
        $this->methodInterceptor = $methodInterceptor;

        $this->internalRequestChannelName = Uuid::uuid4()->toString();
        $this->gateway                    = GatewayProxyBuilder::create(BeforeSendGateway::class, BeforeSendGateway::class, "execute", $this->internalRequestChannelName);
    }

    /**
     * @inheritDoc
     */
    public function relatedChannelName(): string
    {
        return $this->inputChannelName;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return array_merge(
            $this->gateway->resolveRelatedInterfaces($interfaceToCallRegistry),
            $this->methodInterceptor->getMessageHandler()->resolveRelatedInterfaces($interfaceToCallRegistry)
        );
    }

    /**
     * @inheritDoc
     */
    public function getPrecedence(): int
    {
        return $this->methodInterceptor->getPrecedence();
    }

    /**
     * @inheritDoc
     */
    public function build(ReferenceSearchService $referenceSearchService): ChannelInterceptor
    {
        $messageHandler = $this->methodInterceptor->getInterceptingObject()->build(
            InMemoryChannelResolver::createEmpty(),
            $referenceSearchService
        );

        $directChannel = DirectChannel::create();
        $directChannel->subscribe($messageHandler);
        /** @var BeforeSendGateway $gateway */
        $gateway = $this->gateway->buildWithoutProxyObject(
            $referenceSearchService, InMemoryChannelResolver::createFromAssociativeArray(
            [
                $this->internalRequestChannelName => $directChannel
            ]
        )
        );

        return new BeforeSendChannelInterceptor($gateway);
    }

    public function __toString()
    {
        return "{$this->inputChannelName} {$this->methodInterceptor}";
    }
}