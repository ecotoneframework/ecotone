<?php
declare(strict_types=1);


namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Modelling\EventBus;

/**
 * Class LazyEventBusAroundInterceptor
 * @package Ecotone\Modelling\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LazyEventBusAroundInterceptorBuilder implements AroundInterceptorObjectBuilder
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
    public function getInterceptingInterfaceClassName(): string
    {
        return LazyEventBusInterceptor::class;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): object
    {
        $eventBus = $this->eventBusGateway
                        ->build($referenceSearchService, $channelResolver);

        return new LazyEventBusInterceptor($eventBus, $this->inMemoryEventStore);
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return [];
    }
}