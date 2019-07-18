<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\DomainModel\LazyEventBus;

use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Handler\ChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use SimplyCodedSoftware\Messaging\Handler\InputOutputMessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCallRegistry;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\MessageHandler;

/**
 * Class LazyEventBusInterceptorBuilder
 * @package SimplyCodedSoftware\DomainModel\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusInterceptorBuilder extends InputOutputMessageHandlerBuilder
{
    /**
     * @var InMemoryEventStore
     */
    private $inMemoryEventStore;

    public function __construct(InMemoryEventStore $inMemoryEventStore)
    {
        $this->inMemoryEventStore = $inMemoryEventStore;
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
    public function resolveRelatedReferences(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        return [];
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
        $gateway = GatewayProxyBuilder::create("", EventBus::class, "sendWithMetadata", EventBus::CHANNEL_NAME_BY_OBJECT)
            ->withParameterConverters([
                GatewayPayloadBuilder::create("event"),
                GatewayHeadersBuilder::create("metadata")
            ])
            ->build($referenceSearchService, $channelResolver);

        return ServiceActivatorBuilder::createWithDirectReference(
            new LazyEventBusInterceptor($gateway, $this->inMemoryEventStore),
            "publish"
        )
            ->withPassThroughMessageOnVoidInterface(true)
            ->build($channelResolver, $referenceSearchService);
    }
}