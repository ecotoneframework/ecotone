<?php

namespace Ecotone\Modelling\Config;

use Doctrine\Common\Annotations\AnnotationException;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Bridge\BridgeBuilder;
use Ecotone\Messaging\Handler\InterfaceParameter;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\ParameterConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\AllHeadersBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvoker;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Handler\TypeDefinitionException;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateMessageConversionService;
use Ecotone\Modelling\AggregateMessageConversionServiceBuilder;
use Ecotone\Modelling\AggregateMessageHandlerBuilder;
use Ecotone\Modelling\Annotation\Aggregate;
use Ecotone\Modelling\Annotation\Repository;
use Ecotone\Modelling\Annotation\CommandHandler;
use Ecotone\Modelling\Annotation\EventHandler;
use Ecotone\Modelling\Annotation\QueryHandler;
use ReflectionException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package Ecotone\Modelling\Config
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ModellingHandlerModule implements AnnotationModule
{
    const INTEGRATION_MESSAGING_CQRS_MESSAGE_EXECUTING_CHANNEL = "cqrs.execute_message";
    const CQRS_MODULE = "cqrsModule";
    const CQRS_MESSAGE_ROUTER_ENDPOINT_ID = "cqrsMessageRouter";

    /**
     * @var ParameterConverterAnnotationFactory
     */
    private $parameterConverterAnnotationFactory;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateCommandHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $serviceCommandHandlersRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $aggregateQueryHandlerRegistrations;
    /**
     * @var AnnotationRegistration[]
     */
    private $serviceQueryHandlerRegistrations;
    /**
     * @var array|AnnotationRegistration[]
     */
    private $aggregateEventHandlers;
    /**
     * @var array|AnnotationRegistration[]
     */
    private $serviceEventHandlers;
    /**
     * @var string[]
     */
    private $aggregateRepositoryReferenceNames;

    /**
     * CqrsMessagingModule constructor.
     *
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     * @param AnnotationRegistration[] $aggregateCommandHandlerRegistrations
     * @param AnnotationRegistration[] $serviceCommandHandlersRegistrations
     * @param AnnotationRegistration[] $aggregateQueryHandlerRegistrations
     * @param AnnotationRegistration[] $serviceQueryHandlerRegistrations
     * @param AnnotationRegistration[] $aggregateEventHandlers
     * @param AnnotationRegistration[] $serviceEventHandlers
     * @param array $aggregateRepositoryReferenceNames
     */
    private function __construct(
        ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory,
        array $aggregateCommandHandlerRegistrations,
        array $serviceCommandHandlersRegistrations,
        array $aggregateQueryHandlerRegistrations,
        array $serviceQueryHandlerRegistrations,
        array $aggregateEventHandlers,
        array $serviceEventHandlers,
        array $aggregateRepositoryReferenceNames
    )
    {
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
        $this->aggregateCommandHandlerRegistrations = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlerRegistrations = $aggregateQueryHandlerRegistrations;
        $this->serviceCommandHandlersRegistrations = $serviceCommandHandlersRegistrations;
        $this->serviceQueryHandlerRegistrations = $serviceQueryHandlerRegistrations;
        $this->aggregateEventHandlers = $aggregateEventHandlers;
        $this->serviceEventHandlers = $serviceEventHandlers;
        $this->aggregateRepositoryReferenceNames = $aggregateRepositoryReferenceNames;
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        $aggregateRepositoryClasses = $annotationRegistrationService->getAllClassesWithAnnotation(Repository::class);

        $aggregateRepositoryReferenceNames = [];
        foreach ($aggregateRepositoryClasses as $aggregateRepositoryClass) {
            /** @var Repository $aggregateRepositoryAnnotation */
            $aggregateRepositoryAnnotation = $annotationRegistrationService->getAnnotationForClass($aggregateRepositoryClass, Repository::class);

            $aggregateRepositoryReferenceNames[] = $aggregateRepositoryAnnotation->referenceName ? $aggregateRepositoryAnnotation->referenceName : $aggregateRepositoryClass;
        }

        return new self(
            ParameterConverterAnnotationFactory::create(),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, EventHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EventHandler::class),
            $aggregateRepositoryReferenceNames
        );
    }

    /***
     * @param AnnotationRegistration $registration
     *
     * @return string
     */
    public static function getMessageChannelForEventHandler(AnnotationRegistration $registration): string
    {
        /** @var CommandHandler|QueryHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();

        return $methodAnnotation->endpointId . ".target";
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::CQRS_MODULE;
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $configuration->requireReferences($this->aggregateRepositoryReferenceNames);

        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();

            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, self::getHandlerChannel($registration), $annotationForMethod->dropMessageOnNotFound, $annotationForMethod->endpointId);
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, self::getHandlerChannel($registration), $registration->getAnnotationForMethod()->dropMessageOnNotFound, $registration->getAnnotationForMethod()->endpointId);
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            /** @var QueryHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $parameterConverterAnnotations = $annotation->parameterConverters;
            $parameterConverters = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignoreMessage);

            $handledMessageClassName = self::getClassChannelFor($registration);
            $configuration->registerMessageHandler(
                AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith($registration->getClassName(), $registration->getMethodName(), TypeDescriptor::create($handledMessageClassName)->isIterable() ? null : $handledMessageClassName)
                    ->withInputChannelName(self::getHandlerChannel($registration))
                    ->withOutputMessageChannel($registration->getAnnotationForMethod()->outputChannelName)
                    ->withEndpointId($registration->getAnnotationForMethod()->endpointId)
                    ->withAggregateRepositoryFactories($this->aggregateRepositoryReferenceNames)
                    ->withMethodParameterConverters($parameterConverters)
                    ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
            );

            if ($handledMessageClassName) {
                $configuration->registerBeforeMethodInterceptor(
                    MethodInterceptor::create(
                        "",
                        InterfaceToCall::create(AggregateMessageConversionService::class, "convert"),
                        AggregateMessageConversionServiceBuilder::createWith($handledMessageClassName),
                        AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                        $registration->getClassName() . "::" . $registration->getMethodName()
                    )
                );
            }
        }

        foreach ($this->serviceCommandHandlersRegistrations as $registration) {
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();

            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $annotationForMethod->endpointId));
        }
        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $registration->getAnnotationForMethod()->endpointId));
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator(self::getHandlerChannel($registration), $registration, $registration->getAnnotationForMethod()->endpointId));
        }
    }


    public static function getNamedMessageChannelFor(AnnotationRegistration $registration): ?string
    {
        /** @var InputOutputEndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        if ($annotationForMethod instanceof EventHandler) {
            return $registration->getAnnotationForMethod()->listenTo;
        }

        return $annotationForMethod->inputChannelName ?? null;
    }

    public static function getClassChannelFor(AnnotationRegistration $registration) : ?string
    {
        $type = TypeDescriptor::create(ModellingHandlerModule::getMessageClassFor($registration));
        if ($type->isClassOrInterface() && !$type->isClassOfType(TypeDescriptor::create(Message::class))) {
            return $type;
        }

        return null;
    }

    public static function getHandlerChannel(AnnotationRegistration $registration) : string
    {
        /** @var EndpointAnnotation $annotationForMethod */
        $annotationForMethod = $registration->getAnnotationForMethod();

        return $annotationForMethod->endpointId . ".target";
    }

    /**
     * @param AnnotationRegistration $registration
     *
     * @return string|null
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    public static function getMessageClassFor(AnnotationRegistration $registration)
    {
        $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());

        $parameterConverters = ParameterConverterAnnotationFactory::create();
        /** @var ParameterConverterBuilder[] $parameterConverters */
        $parameterConverters = $parameterConverters->createParameterConverters(InterfaceToCall::create($registration->getClassName(), $registration->getMethodName()), $registration->getAnnotationForMethod()->parameterConverters);
        foreach ($parameterConverters as $parameterConverter) {
            if ($parameterConverter->isHandling($interfaceToCall->getFirstParameter())) {
                return null;
            }
        }

        if ($registration->getAnnotationForMethod()->ignoreMessage || $interfaceToCall->hasNoParameters()) {
            return null;
        }

        if (TypeDescriptor::create($interfaceToCall->getFirstParameterTypeHint())->isIterable()) {
            return TypeDescriptor::ARRAY;
        }

        return $interfaceToCall->getFirstParameterTypeHint();
    }


    private function registerAggregateCommandHandler(Configuration $configuration, array $aggregateRepositoryReferenceNames, AnnotationRegistration $registration, string $inputChannelName, bool $dropMessageOnNotFound, string $endpointId): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        /** @var CommandHandler|EventHandler $annotation */
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignoreMessage);

        $handledMessageClassName = self::getMessageClassFor($registration);

        $redirectMethodConverters = [];
        $redirectOnFoundMethod = false;
        if ($annotation->redirectToOnAlreadyExists) {
            $redirectMethodConverters = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences(InterfaceToCall::create($registration->getClassName(), $annotation->redirectToOnAlreadyExists), [], $registration, $annotation->ignoreMessage);
            $redirectOnFoundMethod = $annotation->redirectToOnAlreadyExists;
        }


        $configuration->registerMessageHandler(
            AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith($registration->getClassName(), $registration->getMethodName(), TypeDescriptor::create($handledMessageClassName)->isIterable()  ? null : $handledMessageClassName, $annotation->identifierMetadataMapping)
                ->withInputChannelName($inputChannelName)
                ->withOutputMessageChannel($annotation->outputChannelName)
                ->withEndpointId($endpointId)
                ->withAggregateRepositoryFactories($aggregateRepositoryReferenceNames)
                ->withFilterOutOnNotFound($dropMessageOnNotFound)
                ->withRedirectToOnAlreadyExists($redirectOnFoundMethod, $redirectMethodConverters)
                ->withMethodParameterConverters($parameterConverters)
                ->withRequiredInterceptorNames($annotation->requiredInterceptorNames)
        );

        if ($handledMessageClassName) {
            $configuration->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "",
                    InterfaceToCall::create(AggregateMessageConversionService::class, "convert"),
                    AggregateMessageConversionServiceBuilder::createWith($handledMessageClassName),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    $registration->getClassName() . "::" . $registration->getMethodName()
                )
            );
        }
    }

    /**
     * @param string $inputChannelName
     * @param AnnotationRegistration $registration
     *
     * @param string $endpointId
     * @return ServiceActivatorBuilder
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     */
    private function createServiceActivator(string $inputChannelName, AnnotationRegistration $registration, string $endpointId): ServiceActivatorBuilder
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignoreMessage);

        $messageHandlerBuilder = ServiceActivatorBuilder::create($registration->getReferenceName(), $registration->getMethodName())
            ->withInputChannelName($inputChannelName)
            ->withOutputMessageChannel($annotation->outputChannelName)
            ->withEndpointId($endpointId)
            ->withMethodParameterConverters($parameterConverters)
            ->withRequiredInterceptorNames($annotation->requiredInterceptorNames);

        return $messageHandlerBuilder;
    }
}