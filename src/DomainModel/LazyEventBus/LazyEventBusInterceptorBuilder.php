<?php
declare(strict_types=1);


namespace Ecotone\DomainModel\LazyEventBus;

use Ecotone\DomainModel\EventBus;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHandler;

/**
 * Class LazyEventBusInterceptorBuilder
 * @package Ecotone\DomainModel\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusInterceptorBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var InMemoryEventStore
     */
    private $inMemoryEventStore;
    /**
     * @var GatewayProxyBuilder
     */
    private $eventBusGateway;

    public function __construct(InMemoryEventStore $inMemoryEventStore)
    {
        $this->inMemoryEventStore = $inMemoryEventStore;
        $this->eventBusGateway = GatewayProxyBuilder::create("", EventBus::class, "sendWithMetadata", EventBus::CHANNEL_NAME_BY_OBJECT)
                                    ->withParameterConverters([
                                        GatewayPayloadBuilder::create("event"),
                                        GatewayHeadersBuilder::create("metadata")
                                    ]);
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor(LazyEventBusInterceptor::class, "publish");
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [
            $interfaceToCallRegistry->getFor(LazyEventBusInterceptor::class, "publish"),
            $interfaceToCallRegistry->getFor(EventBus::class, "sendWithMetadata")
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        /** @var EventBus $gateway */
        $gateway = $this->eventBusGateway
            ->build($referenceSearchService, $channelResolver);

        return ServiceActivatorBuilder::createWithDirectReference(
            new LazyEventBusInterceptor($gateway, $this->inMemoryEventStore),
            "publish"
        )
            ->withPassThroughMessageOnVoidInterface(true)
            ->build($channelResolver, $referenceSearchService);
    }
}