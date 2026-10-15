<?php

namespace Ecotone\Modelling\Config;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ConfigurationVariable;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\Headers;
use Ecotone\Api\Attribute\IgnorePayload;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\NamedEvent;
use Ecotone\Api\Attribute\PropagateHeaders;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Config\PriorityBasedOnType;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\Config\MessageHandlerLogger;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Handler\Router\RouterProcessor;
use Ecotone\Messaging\Handler\Router\RouteToChannelResolver;
use Ecotone\Messaging\Handler\ServiceActivator\MessageProcessorActivatorBuilder;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Modelling\Config\Routing\BusRouteSelector;
use Ecotone\Modelling\Config\Routing\BusRoutingKeyResolver;
use Ecotone\Modelling\Config\Routing\BusRoutingMapBuilder;
use Ecotone\Modelling\Config\Routing\CommandBusRouteSelector;
use Ecotone\Modelling\Config\Routing\EventBusRouteSelector;
use Ecotone\Modelling\Config\Routing\QueryBusRouteSelector;
use Ecotone\Modelling\Config\Routing\RoutingEventHandler;
use Ecotone\Modelling\Config\Routing\TypeAliasResolverProcessor;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use ReflectionParameter;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class MessageHandlerRoutingModule implements AnnotationModule
{
    public function __construct(
        private InterfaceToCallRegistry $interfaceToCallRegistry,
        private AnnotationFinder $annotationFinder,
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self(
            $interfaceToCallRegistry,
            $annotationRegistrationService
        );
    }

    /**
     * @return string[] Array of class names for the first parameter. Empty if no class/interface type.
     */
    public static function getFirstParameterClassesIfAny(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): array
    {
        $type = Type::create(self::getFirstParameterTypeFor($registration, $interfaceToCallRegistry));

        $classes = [];
        foreach ($type->getUnionTypes() as $unionType) {
            if ($unionType->isClassOrInterface() && ! $unionType->isIdentifiedBy(Message::class)) {
                $classes[] = $unionType->toString();
            }
        }

        return $classes;
    }

    private static function getFirstParameterTypeFor(AnnotatedFinding $registration, InterfaceToCallRegistry $interfaceToCallRegistry): string
    {
        $interfaceToCall = $interfaceToCallRegistry->getFor($registration->getClassName(), $registration->getMethodName());

        if ($interfaceToCall->hasMethodAnnotation(Type::attribute(IgnorePayload::class)) || $interfaceToCall->hasNoParameters()) {
            return 'array';
        }

        $firstParameterType = $interfaceToCall->getFirstParameter()->getTypeDescriptor();

        if ($firstParameterType->isClassOrInterface() && ! $firstParameterType->isClassOfType(Message::class)) {
            $reflectionParameter = new ReflectionParameter([$registration->getClassName(), $registration->getMethodName()], 0);

            foreach ($reflectionParameter->getAttributes() as $attribute) {
                if (in_array($attribute->getName(), [ConfigurationVariable::class, Header::class, Headers::class, \Ecotone\Api\Attribute\Reference::class])) {
                    return 'array';
                }
            }

            return $firstParameterType;
        }

        return 'array';
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $routingEventHandlers = ExtensionObjectResolver::resolve(RoutingEventHandler::class, $extensionObjects);
        $isRunningForFlowTesting = ExtensionObjectResolver::resolveUnique(ServiceConfiguration::class, $extensionObjects, ServiceConfiguration::createWithDefaults())
            ->isModulePackageEnabled(ModulePackageList::TEST_PACKAGE);
        $commandBusRoutingConfig = new BusRoutingMapBuilder(true, $routingEventHandlers, $messagingConfiguration);
        foreach ($this->annotationFinder->findAnnotatedMethods(CommandHandler::class) as $registration) {
            $destinationChannel = $commandBusRoutingConfig->addRoutesFromAnnotatedFinding($registration, $this->interfaceToCallRegistry);
            /** @var CommandHandler $commandHandler */
            $commandHandler = $registration->getAnnotationForMethod();
            if ($commandHandler->getInputChannelName() && $destinationChannel) {
                $messagingConfiguration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withInputChannelName($commandHandler->getInputChannelName())
                        ->withOutputMessageChannel($destinationChannel)
                        ->withEndpointAnnotations([PriorityBasedOnType::fromAnnotatedFinding($registration)->toAttributeDefinition()])
                );
            }
        }
        $queryBusRouting = new BusRoutingMapBuilder(true, $routingEventHandlers, $messagingConfiguration);
        foreach ($this->annotationFinder->findAnnotatedMethods(QueryHandler::class) as $registration) {
            $destinationChannel = $queryBusRouting->addRoutesFromAnnotatedFinding($registration, $this->interfaceToCallRegistry);
            /** @var QueryHandler $commandHandler */
            $queryHandler = $registration->getAnnotationForMethod();
            if ($queryHandler->getInputChannelName()) {
                $messagingConfiguration->registerMessageHandler(
                    BridgeBuilder::create()
                        ->withInputChannelName($queryHandler->getInputChannelName())
                        ->withOutputMessageChannel($destinationChannel)
                );
            }
        }
        $eventBusRouting = new BusRoutingMapBuilder(false, $routingEventHandlers, $messagingConfiguration);
        foreach ($this->annotationFinder->findAnnotatedMethods(EventHandler::class) as $registration) {
            $eventBusRouting->addRoutesFromAnnotatedFinding($registration, $this->interfaceToCallRegistry);
        }
        $eventBusTypeAliases = [];
        foreach ($this->annotationFinder->findAnnotatedClasses(NamedEvent::class) as $className) {
            $attribute = $this->annotationFinder->getAttributeForClass($className, NamedEvent::class);
            $eventBusRouting->addObjectAlias($className, $attribute->getName());
            $eventBusTypeAliases[$attribute->getName()] = $className;
        }

        $messagingConfiguration
            ->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME)
                    ->chain($this->buildRouterProcessor($commandBusRoutingConfig, MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME, $isRunningForFlowTesting))
            )
            ->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME)
                    ->chain($this->buildRouterProcessor($queryBusRouting, MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME, $isRunningForFlowTesting))
            )
            ->registerMessageHandler(
                MessageProcessorActivatorBuilder::create()
                    ->withInputChannelName(MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME)
                    ->chain(new Definition(TypeAliasResolverProcessor::class, [
                        $eventBusTypeAliases,
                        MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME,
                    ]))
                    ->chain($this->buildRouterProcessor($eventBusRouting, MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME, $isRunningForFlowTesting, false))
            );

        $messagingConfiguration->registerServiceDefinition(
            MessageHeadersPropagatorInterceptor::class,
            new Definition(MessageHeadersPropagatorInterceptor::class)
        );
        $messagingConfiguration->registerServiceDefinition(
            MessageHandlerLogger::class,
            new Definition(MessageHandlerLogger::class)
        );

        $propagateHeadersInterfaceToCall = $interfaceToCallRegistry->getFor(MessageHeadersPropagatorInterceptor::class, 'propagateHeaders');
        $storeHeadersInterfaceToCall = $interfaceToCallRegistry->getFor(MessageHeadersPropagatorInterceptor::class, 'storeHeaders');
        $pointcut =
            CommandBus::class . '||' .
            EventBus::class . '||' .
            QueryBus::class . '||' .
            AsynchronousRunningEndpoint::class . '||' .
            PropagateHeaders::class . '||' .
            MessageGateway::class;

        $messagingConfiguration
            ->registerBeforeMethodInterceptor(
                MethodInterceptorBuilder::create(
                    Reference::to(MessageHeadersPropagatorInterceptor::class),
                    $propagateHeadersInterfaceToCall,
                    Precedence::ENDPOINT_HEADERS_PRECEDENCE - 2,
                    $pointcut,
                    true,
                    [
                        AllHeadersBuilder::createWith('headers'),
                    ]
                )
            )
            ->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::create(
                    MessageHeadersPropagatorInterceptor::class,
                    $storeHeadersInterfaceToCall,
                    Precedence::ENDPOINT_HEADERS_PRECEDENCE - 1,
                    $pointcut,
                    ParameterConverterAnnotationFactory::create()->createParameterConverters($storeHeadersInterfaceToCall),
                )
            );
    }

    private function buildRouterProcessor(BusRoutingMapBuilder $busRoutingConfig, string $channel, bool $isRunningForFlowTesting, bool $isResolutionRequired = true): Definition
    {
        $busRouteSelectorClass = match ($channel) {
            MessageBusChannel::COMMAND_CHANNEL_NAME_BY_NAME => CommandBusRouteSelector::class,
            MessageBusChannel::QUERY_CHANNEL_NAME_BY_NAME => QueryBusRouteSelector::class,
            MessageBusChannel::EVENT_CHANNEL_NAME_BY_NAME => EventBusRouteSelector::class,
            default => BusRouteSelector::class,
        };
        $busRouteSelectorArguments = [
            $busRoutingConfig->compile(),
            new Definition(BusRoutingKeyResolver::class, [$channel]),
            new Reference(LoggingGateway::class),
        ];
        if (in_array($busRouteSelectorClass, [CommandBusRouteSelector::class, QueryBusRouteSelector::class], true)) {
            $busRouteSelectorArguments[] = $isRunningForFlowTesting;
        }

        return new Definition(RouterProcessor::class, [
            new Definition($busRouteSelectorClass, $busRouteSelectorArguments),
            new Definition(RouteToChannelResolver::class, [new Reference(ChannelResolver::class)]),
            $isResolutionRequired, // Single route if resolution is required
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getModuleExtensions(ServiceConfiguration $serviceConfiguration, array $serviceExtensions): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
