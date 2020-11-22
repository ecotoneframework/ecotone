<?php

namespace Ecotone\Modelling\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\AnnotationFinder\AnnotatedDefinition;
use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\Asynchronous;
use Ecotone\Messaging\Annotation\ClassReference;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\HeaderBuilder;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\Transformer\TransformerBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateIdentifierRetrevingServiceBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\Annotation\ChangingHeaders;
use Ecotone\Modelling\Annotation\IgnorePayload;
use Ecotone\Modelling\CallAggregateServiceBuilder;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\LoadAggregateMode;
use Ecotone\Modelling\QueryBus;
use Ecotone\Modelling\SaveAggregateServiceBuilder;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\CallAggregateService;
use Ecotone\Modelling\LoadAggregateServiceBuilder;
use Ramsey\Uuid\Uuid;
use ReflectionException;

#[ModuleAnnotation]
class BusModule extends NoExternalConfigurationModule implements AnnotationModule
{
    const MODULE_NAME                    = "busModule";

    const COMMAND_CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.command_by_object";
    const COMMAND_CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.command_by_name";

    const QUERY_CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.query_by_object";
    const QUERY_CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.query_by_name";

    const EVENT_CHANNEL_NAME_BY_OBJECT = "ecotone.modelling.bus.event_by_object";
    const EVENT_CHANNEL_NAME_BY_NAME   = "ecotone.modelling.bus.event_by_name";
    /**
     * @var GatewayHeadersBuilder[]
     */
    private array $gateways;

    /**
     * @var GatewayProxyBuilder[]
     */
    private function __construct(array $gateways)
    {
        $this->gateways = $gateways;
    }

    public static function create(AnnotationFinder $annotationRegistrationService): static
    {
        return new self([
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, "send", self::COMMAND_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("command")]),
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, "sendWithMetadata", self::COMMAND_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("command"), GatewayHeadersBuilder::create("metadata")]),
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, "sendWithRouting", self::COMMAND_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("command"),
                    GatewayHeaderBuilder::create("routingKey", self::COMMAND_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("commandMediaType", MessageHeaders::CONTENT_TYPE)
                ]),
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, "sendWithRoutingAndMetadata", self::COMMAND_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("command"),
                    GatewayHeadersBuilder::create("metadata"),
                    GatewayHeaderBuilder::create("routingKey", self::COMMAND_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("commandMediaType", MessageHeaders::CONTENT_TYPE)
                ]),

            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, "send", self::QUERY_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("query")]),
            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, "sendWithMetadata", self::QUERY_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("query"), GatewayHeadersBuilder::create("metadata")]),
            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, "sendWithRouting", self::QUERY_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("query"),
                    GatewayHeaderBuilder::create("routingKey", self::QUERY_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("queryMediaType", MessageHeaders::CONTENT_TYPE)
                ]),
            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, "sendWithRoutingAndMetadata", self::QUERY_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("query"),
                    GatewayHeadersBuilder::create("metadata"),
                    GatewayHeaderBuilder::create("routingKey", self::QUERY_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("queryMediaType", MessageHeaders::CONTENT_TYPE)
                ]),

            GatewayProxyBuilder::create(EventBus::class, EventBus::class, "publish", self::EVENT_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("event")]),
            GatewayProxyBuilder::create(EventBus::class, EventBus::class, "publishWithMetadata", self::EVENT_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create("event"), GatewayHeadersBuilder::create("metadata")]),
            GatewayProxyBuilder::create(EventBus::class, EventBus::class, "publishWithRouting", self::EVENT_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("event"),
                    GatewayHeaderBuilder::create("routingKey", self::EVENT_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("eventMediaType", MessageHeaders::CONTENT_TYPE)
                ]),
            GatewayProxyBuilder::create(EventBus::class, EventBus::class, "publishWithRoutingAndMetadata", self::EVENT_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create("event"),
                    GatewayHeadersBuilder::create("metadata"),
                    GatewayHeaderBuilder::create("routingKey", self::EVENT_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create("eventMediaType", MessageHeaders::CONTENT_TYPE)
                ]),
        ]);
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        foreach ($this->gateways as $gateway) {
            $configuration->registerGatewayBuilder($gateway);
        }
    }

    public function canHandle($extensionObject): bool
    {
        return false;
    }
}