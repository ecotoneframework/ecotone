<?php

namespace Ecotone\Modelling;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Handler\Chain\ChainMessageHandlerBuilder;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\ClassDefinition;
use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayHeadersBuilder;
use Ecotone\Messaging\Handler\Gateway\ParameterToMessageConverter\GatewayPayloadBuilder;
use Ecotone\Messaging\Handler\InputOutputMessageHandlerBuilder;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithOutputChannel;
use Ecotone\Messaging\Handler\MessageHandlerBuilderWithParameterConverters;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\ReferenceNotFoundException;
use Ecotone\Messaging\Handler\ReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHandler;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\Annotation\AggregateEvents;
use Ecotone\Modelling\Annotation\AggregateFactory;
use Ecotone\Modelling\Annotation\AggregateIdentifier;
use Ecotone\Modelling\Annotation\TargetAggregateIdentifier;
use Ecotone\Modelling\Annotation\TargetAggregateVersion;
use Ecotone\Modelling\Annotation\Version;
use Ecotone\Modelling\LazyEventBus\LazyEventBus;
use ReflectionException;

/**
 * Class AggregateCallingCommandHandlerBuilder
 * @package Ecotone\Modelling
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AggregateMessageHandlerBuilder extends InputOutputMessageHandlerBuilder implements MessageHandlerBuilderWithParameterConverters, MessageHandlerBuilderWithOutputChannel
{
    const DEFAULT_FILTER_OUT_ON_NOT_FOUND = false;

    /**
     * @var string
     */
    private $aggregateClassName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var InterfaceToCall
     */
    private $interfaceToCall;
    /**
     * @var array|ParameterConverterBuilder[]
     */
    private $methodParameterConverterBuilders = [];
    /**
     * @var string[]
     */
    private $requiredReferences = [];
    /**
     * @var bool
     */
    private $isCommandHandler;
    /**
     * @var string[]
     */
    private $aggregateRepositoryReferenceNames = [];
    /**
     * @var bool
     */
    private $isFactoryMethod;
    /**
     * @var bool
     */
    private $isVoidMethod;
    /**
     * @var string[]
     */
    private $messageIdentifierMapping;
    /**
     * @var ?array
     */
    private $versionMapping;
    /**
     * @var bool
     */
    private $dropMessageOnNotFound = self::DEFAULT_FILTER_OUT_ON_NOT_FOUND;
    /**
     * @var string
     */
    private $withFactoryRedirectOnFoundMethodName = "";
    /**
     * @var ParameterConverterBuilder[]
     */
    private $withFactoryRedirectOnFoundParameterConverters = [];
    /**
     * @var string|null
     */
    private $aggregateMethodWithEvents;
    /**
     * @var string|null
     */
    private $eventSourcedFactoryMethod;

    /**
     * AggregateCallingCommandHandlerBuilder constructor.
     *
     * @param string $aggregateClassName
     * @param string $methodName
     * @param bool $isCommandHandler
     * @param string|null $handledMessageClassName
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function __construct(string $aggregateClassName, string $methodName, bool $isCommandHandler, ?string $handledMessageClassName)
    {
        $this->aggregateClassName = $aggregateClassName;
        $this->methodName = $methodName;
        $this->isCommandHandler = $isCommandHandler;

        $this->initialize($aggregateClassName, $handledMessageClassName);
    }

    /**
     * @param string $aggregateClassName
     * @param string|null $handledMessageClassName
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     * @throws AnnotationException
     */
    private function initialize(string $aggregateClassName, ?string $handledMessageClassName): void
    {
        $interfaceToCall = InterfaceToCall::create($this->aggregateClassName, $this->methodName);
        $this->isFactoryMethod = $interfaceToCall->isStaticallyCalled();
        $this->isVoidMethod = $interfaceToCall->getReturnType()->isVoid();

        $classDefinition = ClassDefinition::createFor(TypeDescriptor::create($aggregateClassName));
        $aggregateDefaultIdentifiers = [];
        $aggregateMethodWithEvents = null;
        $aggregateVersionPropertyName = null;
        $eventSourcedFactoryMethod = null;

        $aggregateFactoryAnnotation = TypeDescriptor::create(AggregateFactory::class);
        $aggregateEventsAnnotation = TypeDescriptor::create(AggregateEvents::class);
        foreach ($classDefinition->getPublicMethodNames() as $method) {
            $methodToCheck = InterfaceToCall::create($aggregateClassName, $method);

            if ($methodToCheck->hasMethodAnnotation($aggregateFactoryAnnotation)) {
                $factoryMethodInterface = InterfaceToCall::create($aggregateClassName, $method);
                if (!$factoryMethodInterface->hasSingleArgument()) {
                    throw InvalidArgumentException::create("Event sourced factory method {$aggregateClassName}:{$method} should contain only one iterable parameter");
                }
                if (!$factoryMethodInterface->isStaticallyCalled()) {
                    throw InvalidArgumentException::create("Event sourced factory method {$aggregateClassName}:{$method} should be static");
                }

                $eventSourcedFactoryMethod = $method;
                break;
            }
            if ($methodToCheck->hasMethodAnnotation($aggregateEventsAnnotation)) {
                $aggregateMethodWithEvents = $method;
                break;
            }
        }

        $aggregateIdentififerAnnotation = TypeDescriptor::create(AggregateIdentifier::class);
        $versionAnnotation = TypeDescriptor::create(Version::class);
        foreach ($classDefinition->getProperties() as $property) {
            if ($property->hasAnnotation($aggregateIdentififerAnnotation)) {
                $aggregateDefaultIdentifiers[$property->getName()] = null;
            }
            if ($property->hasAnnotation($versionAnnotation)) {
                $aggregateVersionPropertyName = $property->getName();
            }
        }

        if (empty($aggregateDefaultIdentifiers)) {
            throw InvalidArgumentException::create("Aggregate {$aggregateClassName} has no identifiers defined. How you forgot to mark @AggregateIdentifier?");
        }

        $messageProperties = [];
        $aggregateVersionMapping = null;
        if ($handledMessageClassName) {
            $handledMessageClassNameDefinition = ClassDefinition::createFor(TypeDescriptor::create($handledMessageClassName));
            $targetAggregateIdentifierAnnotation = TypeDescriptor::create(TargetAggregateIdentifier::class);
            $targetAggregateVersion = TypeDescriptor::create(TargetAggregateVersion::class);
            foreach ($handledMessageClassNameDefinition->getProperties() as $property) {
                if ($property->hasAnnotation($targetAggregateIdentifierAnnotation)) {
                    /** @var TargetAggregateIdentifier $annotation */
                    $annotation = $property->getAnnotation($targetAggregateIdentifierAnnotation);
                    $mappingName = $annotation->identifierName ? $annotation->identifierName : $property->getName();
                    $aggregateDefaultIdentifiers[$mappingName] = $property->getName();
                }
                if ($property->hasAnnotation($targetAggregateVersion)) {
                    $aggregateVersionMapping[$property->getName()] = $aggregateVersionPropertyName;
                }
            }

            $messageProperties = $handledMessageClassNameDefinition->getProperties();
        }

        if (!$aggregateVersionMapping && $aggregateVersionPropertyName) {
            $aggregateVersionMapping[$aggregateVersionPropertyName] = $aggregateVersionPropertyName;
        }

        foreach ($aggregateDefaultIdentifiers as $aggregateIdentifierName => $aggregateIdentifierMappingKey) {
            if (is_null($aggregateIdentifierMappingKey)) {
                $mappingKey = null;
                foreach ($messageProperties as $property) {
                    if ($aggregateIdentifierName === $property->getName()) {
                        $mappingKey = $property->getName();
                    }
                }

                if (is_null($handledMessageClassName) && is_null($mappingKey)) {
                    $aggregateDefaultIdentifiers[$aggregateIdentifierName] = $aggregateIdentifierName;
                } else if (is_null($mappingKey) && !$this->isFactoryMethod) {
                    throw new InvalidArgumentException("Can't find aggregate identifier mapping `{$aggregateIdentifierName}` in {$handledMessageClassName} for {$aggregateClassName}. How you forgot to mark @TargetAggregateIdentifier?");
                } else {
                    $aggregateDefaultIdentifiers[$aggregateIdentifierName] = $mappingKey;
                }
            }
        }

        $this->messageIdentifierMapping = $aggregateDefaultIdentifiers;
        $this->versionMapping = $aggregateVersionMapping;
        $this->interfaceToCall = $interfaceToCall;
        $this->aggregateMethodWithEvents = $aggregateMethodWithEvents;
        $this->eventSourcedFactoryMethod = $eventSourcedFactoryMethod;
    }

    /**
     * @param string $aggregateClassName
     * @param string $methodName
     * @param string|null $handledMessageClassName
     *
     * @return AggregateMessageHandlerBuilder
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function createAggregateCommandHandlerWith(string $aggregateClassName, string $methodName, ?string $handledMessageClassName): self
    {
        return new self($aggregateClassName, $methodName, true, $handledMessageClassName);
    }

    /**
     * @param string $aggregateClassName
     * @param string|null $methodName
     *
     * @param string $handledMessageClassName
     * @return AggregateMessageHandlerBuilder
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function createAggregateQueryHandlerWith(string $aggregateClassName, string $methodName, ?string $handledMessageClassName): self
    {
        return new self($aggregateClassName, $methodName, false, $handledMessageClassName);
    }

    /**
     * @inheritDoc
     */
    public function getParameterConverters(): array
    {
        return $this->methodParameterConverterBuilders;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferenceNames(): array
    {
        return $this->requiredReferences;
    }

    /**
     * @param string[] $aggregateRepositoryReferenceNames
     * @return AggregateMessageHandlerBuilder
     */
    public function withAggregateRepositoryFactories(array $aggregateRepositoryReferenceNames): self
    {
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;

        return $this;
    }

    /**
     * @param bool $dropMessageOnNotFound
     *
     * @return AggregateMessageHandlerBuilder
     */
    public function withFilterOutOnNotFound(bool $dropMessageOnNotFound): self
    {
        $this->dropMessageOnNotFound = $dropMessageOnNotFound;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withMethodParameterConverters(array $methodParameterConverterBuilders): self
    {
        Assert::allInstanceOfType($methodParameterConverterBuilders, ParameterConverterBuilder::class);

        $this->methodParameterConverterBuilders = $methodParameterConverterBuilders;

        return $this;
    }

    /**
     * @param string $redirectOnFoundMethod
     * @param ParameterConverterBuilder[] $methodConverters
     * @return AggregateMessageHandlerBuilder
     */
    public function withRedirectToOnAlreadyExists(string $redirectOnFoundMethod, array $methodConverters): self
    {
        $this->withFactoryRedirectOnFoundMethodName = $redirectOnFoundMethod;
        $this->withFactoryRedirectOnFoundParameterConverters = $methodConverters;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function build(ChannelResolver $channelResolver, ReferenceSearchService $referenceSearchService): MessageHandler
    {
        $chainCqrsMessageHandler = ChainMessageHandlerBuilder::create();
        $aggregateRepository = $this->getAggregateRepository($referenceSearchService);

        if ($aggregateRepository instanceof EventSourcedRepository && !$this->eventSourcedFactoryMethod) {
            $repositoryClass = get_class($aggregateRepository);
            throw InvalidArgumentException::create("Based on your repository {$repositoryClass}, you want to create Event Sourced Aggregate. You must define static method marked with @AggregateFactory for aggregate recreation from events");
        }

        $chainCqrsMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    $this->createLoadAggregateService($aggregateRepository),
                    "load"
                )
            );

        $methodParameterConverters = [];
        foreach ($this->methodParameterConverterBuilders as $parameterConverterBuilder) {
            $methodParameterConverters[] = $parameterConverterBuilder->build($referenceSearchService);
        }

        $withFactoryRedirectOnFoundParameterConverters = [];
        foreach ($this->withFactoryRedirectOnFoundParameterConverters as $redirectOnFoundParameterConverter) {
            $withFactoryRedirectOnFoundParameterConverters[] = $redirectOnFoundParameterConverter->build($referenceSearchService);
        }

        $chainCqrsMessageHandler
            ->chain(
                ServiceActivatorBuilder::createWithDirectReference(
                    new CallAggregateService($channelResolver, $methodParameterConverters, AroundInterceptorReference::createAroundInterceptors($referenceSearchService, $this->orderedAroundInterceptors), $referenceSearchService, $this->withFactoryRedirectOnFoundMethodName, $withFactoryRedirectOnFoundParameterConverters, $this->eventSourcedFactoryMethod),
                    "call"
                )->withPassThroughMessageOnVoidInterface($this->isVoidMethod)
            );

        $lazyEventBus = GatewayProxyBuilder::create("", LazyEventBus::class, "sendWithMetadata", LazyEventBus::CHANNEL_NAME)
            ->withParameterConverters([
                GatewayPayloadBuilder::create("event"),
                GatewayHeadersBuilder::create("metadata")
            ])
            ->build($referenceSearchService, $channelResolver);
        if ($this->isCommandHandler) {
            $chainCqrsMessageHandler
                ->chain(
                    ServiceActivatorBuilder::createWithDirectReference(
                        new SaveAggregateService(
                            $this->aggregateClassName,
                            $aggregateRepository,
                            PropertyEditorAccessor::create($referenceSearchService),
                            $this->getPropertyReaderAccessor(),
                            $lazyEventBus,
                            $this->aggregateMethodWithEvents,
                            $this->versionMapping
                        ),
                        "save"
                    )
                );
        }

        return $chainCqrsMessageHandler
            ->withOutputMessageChannel($this->outputMessageChannelName)
            ->build($channelResolver, $referenceSearchService);
    }

    /**
     * @param ReferenceSearchService $referenceSearchService
     * @return object|StandardRepository|EventSourcedRepository
     * @throws InvalidArgumentException
     * @throws ReferenceNotFoundException
     * @throws MessagingException
     */
    private function getAggregateRepository(ReferenceSearchService $referenceSearchService): object
    {
        $aggregateRepository = null;
        foreach ($this->aggregateRepositoryReferenceNames as $aggregateRepositoryName) {
            /** @var StandardRepository|EventSourcedRepository $aggregateRepository */
            $aggregateRepositoryToCheck = $referenceSearchService->get($aggregateRepositoryName);
            if ($aggregateRepositoryToCheck->canHandle($this->aggregateClassName)) {
                $aggregateRepository = $aggregateRepositoryToCheck;
                break;
            }
        }
        Assert::notNull($aggregateRepository, "Aggregate Repository not found for {$this->aggregateClassName}:{$this->methodName}");
        return $aggregateRepository;
    }

    /**
     * @param object $aggregateRepository
     * @return LoadAggregateService
     */
    public function createLoadAggregateService(object $aggregateRepository): LoadAggregateService
    {
        return new LoadAggregateService(
            $aggregateRepository,
            $this->aggregateClassName,
            $this->methodName,
            $this->isFactoryMethod,
            $this->messageIdentifierMapping,
            $this->versionMapping,
            $this->getPropertyReaderAccessor(),
            $this->dropMessageOnNotFound,
            (bool)$this->withFactoryRedirectOnFoundMethodName,
            $this->eventSourcedFactoryMethod,
            );
    }

    /**
     * @return PropertyReaderAccessor
     */
    private function getPropertyReaderAccessor(): PropertyReaderAccessor
    {
        $propertyReader = new PropertyReaderAccessor();
        return $propertyReader;
    }

    /**
     * @inheritDoc
     */
    public function resolveRelatedInterfaces(InterfaceToCallRegistry $interfaceToCallRegistry): iterable
    {
        $interfaces = [
            $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName),
            $interfaceToCallRegistry->getFor(LoadAggregateService::class, "load"),
            $interfaceToCallRegistry->getFor(CallAggregateService::class, "call"),
            $interfaceToCallRegistry->getFor(SaveAggregateService::class, "save")
        ];

        if ($this->withFactoryRedirectOnFoundMethodName) {
            $interfaces[] = $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->withFactoryRedirectOnFoundMethodName);
        }

        return $interfaces;
    }

    /**
     * @inheritDoc
     */
    public function getInterceptedInterface(InterfaceToCallRegistry $interfaceToCallRegistry): InterfaceToCall
    {
        return $interfaceToCallRegistry->getFor($this->aggregateClassName, $this->methodName);
    }

    public function __toString()
    {
        return sprintf("Aggregate Handler - %s:%s with name `%s` for input channel `%s`", $this->aggregateClassName, $this->methodName, $this->getEndpointId(), $this->getInputMessageChannelName());
    }
}