<?php

declare(strict_types=1);

namespace Ecotone\Tracing\Config;

use function array_map;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\InternalHandler;
use Ecotone\Api\Attribute\MessageGateway;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\Attribute\OrchestratorGateway;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\MessageChannelBuilder;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\Gateway\CommandBus;
use Ecotone\Api\Gateway\DistributedBus;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Gateway\EventBus;
use Ecotone\Api\Gateway\QueryBus;
use Ecotone\Api\Interceptor\Precedence;
use Ecotone\Api\Tracing\CurrentTrace;
use Ecotone\Api\Tracing\SpanRecorder;
use Ecotone\Api\Tracing\TracingConfiguration;
use Ecotone\Messaging\Channel\Collector\MessageCollectionObserver;
use Ecotone\Messaging\Channel\Collector\UnobservedMessageCollection;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ExtensionObjectResolver;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\NoExternalConfigurationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptorBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\MessageHandling\MetadataPropagator\MessageHeadersPropagatorInterceptor;
use Ecotone\Projecting\PartitionBatchExecutorHandler;
use Ecotone\Projecting\ProjectionOperationEndpoint;
use Ecotone\Projecting\ProjectionTriggerEndpoint;
use Ecotone\Tracing\CompositeSpanRecorder;
use Ecotone\Tracing\EcotoneTracer;
use Ecotone\Tracing\MessageSpanAttributes;
use Ecotone\Tracing\Tracer;
use Ecotone\Tracing\TracerCurrentTrace;
use Ecotone\Tracing\TracingDisabledCurrentTrace;
use Ecotone\Tracing\TracingInterceptor;
use Ecotone\Tracing\TracingMessageCollectionObserver;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class TracingModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private const USER_MESSAGE_GATEWAYS_POINTCUT = MessageGateway::class . '&&not(' . CommandBus::class . ')&&not(' . EventBus::class . ')&&not(' . QueryBus::class . ')';

    /**
     * @param array<string, string> $endpointIdsByHandler
     */
    private function __construct(
        private array $endpointIdsByHandler,
    ) {
    }

    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self(self::endpointIdsByHandler($annotationRegistrationService));
    }



    /**
     * @return array<string, string>
     */
    private static function endpointIdsByHandler(AnnotationFinder $annotationFinder): array
    {
        $endpointIds = [];
        foreach ([CommandHandler::class, QueryHandler::class, EventHandler::class, InternalHandler::class] as $attributeClassName) {
            foreach ($annotationFinder->findAnnotatedMethods($attributeClassName) as $annotatedMethod) {
                $endpointIds[$annotatedMethod->getClassName() . '::' . $annotatedMethod->getMethodName()] = $annotatedMethod->getAnnotationForMethod()->getEndpointId();
            }
        }

        return $endpointIds;
    }

    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        $tracingConfiguration = ExtensionObjectResolver::resolveUnique(TracingConfiguration::class, $extensionObjects, TracingConfiguration::createWithDefaults());
        $tracerReferences = ExtensionObjectResolver::resolve(TracerReference::class, $extensionObjects);

        $spanRecorderReferences = ExtensionObjectResolver::resolve(SpanRecorderReference::class, $extensionObjects);
        if ($tracingConfiguration->spanRecorderReferenceNames === [] && $tracerReferences === [] && $spanRecorderReferences === []) {
            $messagingConfiguration->registerServiceDefinition(CurrentTrace::class, new Definition(TracingDisabledCurrentTrace::class));
            $messagingConfiguration->registerServiceDefinition(MessageCollectionObserver::class, new Definition(UnobservedMessageCollection::class));

            return;
        }
        $messagingConfiguration->registerServiceDefinition(CurrentTrace::class, new Definition(TracerCurrentTrace::class, [new Reference(Tracer::class)]));
        $messagingConfiguration->registerServiceDefinition(MessageCollectionObserver::class, new Definition(TracingMessageCollectionObserver::class, [new Reference(Tracer::class)]));

        $spanRecorders = [
            ...array_map(fn (string $referenceName): Reference => new Reference($referenceName), $tracingConfiguration->spanRecorderReferenceNames),
            ...array_map(fn (SpanRecorderReference $reference): Reference => new Reference($reference->referenceName), $spanRecorderReferences),
        ];

        $messagingConfiguration->registerServiceDefinition(SpanRecorder::class, new Definition(CompositeSpanRecorder::class, [$spanRecorders]));
        $messagingConfiguration->registerServiceDefinition(
            Tracer::class,
            $tracerReferences === []
                ? new Definition(EcotoneTracer::class, [new Reference(EcotoneClockInterface::class), new Reference(SpanRecorder::class)])
                : new Reference($tracerReferences[0]->referenceName)
        );
        $messagingConfiguration->addCompilerPass(new RegisterSpanEventLoggerCompilerPass());
        $messagingConfiguration->addCompilerPass(new TraceEventRoutesCompilerPass());
        $messagingConfiguration->addCompilerPass(new TraceDynamicConsistencyBoundaryCompilerPass());
        $messagingConfiguration->registerServiceDefinition(
            TracingInterceptor::class,
            new Definition(TracingInterceptor::class, [new Reference(Tracer::class), new Reference(MessageBuilder::class), new Reference(MessageSpanAttributes::class), $this->endpointIdsByHandler])
        );
        $messagingConfiguration->registerServiceDefinition(
            MessageSpanAttributes::class,
            new Definition(MessageSpanAttributes::class, [$tracingConfiguration->spanAttributesFromAllHeaders, $tracingConfiguration->spanAttributeHeaders])
        );

        foreach ([
            'traceCommandHandler' => [CommandHandler::class, Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceQueryHandler' => [QueryHandler::class, Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceEventHandler' => [EventHandler::class, Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceMessageHandler' => [InternalHandler::class . '&&not(' . MessageHeadersPropagatorInterceptor::class . ')', Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceProjection' => [ProjectionTriggerEndpoint::class, Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceProjectionBatch' => [PartitionBatchExecutorHandler::class, Precedence::TRACING_HANDLER_PRECEDENCE],
            'traceProjectionOperation' => [ProjectionOperationEndpoint::class, Precedence::TRACING_PRECEDENCE],
            'traceCommandBus' => [CommandBus::class, Precedence::TRACING_PRECEDENCE],
            'traceQueryBus' => [QueryBus::class, Precedence::TRACING_PRECEDENCE],
            'traceEventBus' => [EventBus::class, Precedence::TRACING_PRECEDENCE],
            'traceAsynchronousEndpoint' => [AsynchronousRunningEndpoint::class, Precedence::TRACING_PRECEDENCE],
            'traceDistributedBus' => [DistributedBus::class, Precedence::TRACING_PRECEDENCE],
            'traceMessageGateway' => [self::USER_MESSAGE_GATEWAYS_POINTCUT, Precedence::TRACING_PRECEDENCE],
            'traceOrchestratorGateway' => [OrchestratorGateway::class, Precedence::TRACING_PRECEDENCE],
        ] as $method => [$pointcut, $precedence]) {
            $messagingConfiguration->registerAroundMethodInterceptor(
                AroundInterceptorBuilder::create(
                    TracingInterceptor::class,
                    $interfaceToCallRegistry->getFor(TracingInterceptor::class, $method),
                    $precedence,
                    $pointcut
                )
            );
        }

        foreach (ExtensionObjectResolver::resolve(MessageChannelBuilder::class, $extensionObjects) as $messageChannelBuilder) {
            if ($messageChannelBuilder->isPollable()) {
                $messagingConfiguration->registerChannelInterceptor(new TracingChannelInterceptorBuilder($messageChannelBuilder->getMessageChannelName()));
            }
        }

        $messagingConfiguration->registerBeforeMethodInterceptor(
            MethodInterceptorBuilder::create(
                Reference::to(TracingInterceptor::class),
                $interfaceToCallRegistry->getFor(TracingInterceptor::class, 'provideContextForDistributedBus'),
                Precedence::DEFAULT_PRECEDENCE,
                DistributedBus::class
            )
        );
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }



}
