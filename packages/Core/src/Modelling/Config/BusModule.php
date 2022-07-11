<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeaderBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\CommandBus;
use Ecotone\Modelling\EventBus;
use Ecotone\Modelling\QueryBus;

#[ModuleAnnotation]
class BusModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME                    = 'busModule';

    public const COMMAND_CHANNEL_NAME_BY_OBJECT = 'ecotone.modelling.bus.command_by_object';
    public const COMMAND_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.command_by_name';

    public const QUERY_CHANNEL_NAME_BY_OBJECT = 'ecotone.modelling.bus.query_by_object';
    public const QUERY_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.query_by_name';

    public const EVENT_CHANNEL_NAME_BY_OBJECT = 'ecotone.modelling.bus.event_by_object';
    public const EVENT_CHANNEL_NAME_BY_NAME   = 'ecotone.modelling.bus.event_by_name';
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

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self([
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, 'send', self::COMMAND_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create('command'), GatewayHeadersBuilder::create('metadata')]),
            GatewayProxyBuilder::create(CommandBus::class, CommandBus::class, 'sendWithRouting', self::COMMAND_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('command'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('routingKey', self::COMMAND_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create('commandMediaType', MessageHeaders::CONTENT_TYPE),
                ]),

            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, 'send', self::QUERY_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('query'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('expectedReturnedMediaType', MessageHeaders::REPLY_CONTENT_TYPE),
                ]),
            GatewayProxyBuilder::create(QueryBus::class, QueryBus::class, 'sendWithRouting', self::QUERY_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('query'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('routingKey', self::QUERY_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create('queryMediaType', MessageHeaders::CONTENT_TYPE),
                    GatewayHeaderBuilder::create('expectedReturnedMediaType', MessageHeaders::REPLY_CONTENT_TYPE),
                ]),

            GatewayProxyBuilder::create(EventBus::class, EventBus::class, 'publish', self::EVENT_CHANNEL_NAME_BY_OBJECT)
                ->withParameterConverters([GatewayPayloadBuilder::create('event'), GatewayHeadersBuilder::create('metadata')]),
            GatewayProxyBuilder::create(EventBus::class, EventBus::class, 'publishWithRouting', self::EVENT_CHANNEL_NAME_BY_NAME)
                ->withParameterConverters([
                    GatewayPayloadBuilder::create('event'),
                    GatewayHeadersBuilder::create('metadata'),
                    GatewayHeaderBuilder::create('routingKey', self::EVENT_CHANNEL_NAME_BY_NAME),
                    GatewayHeaderBuilder::create('eventMediaType', MessageHeaders::CONTENT_TYPE),
                ]),
        ]);
    }

    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
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
