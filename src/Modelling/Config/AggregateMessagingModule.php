<?php

namespace Ecotone\Modelling\Config;

use Doctrine\Common\Annotations\AnnotationException;
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
class AggregateMessagingModule implements AnnotationModule
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

    public static function getMessageClassOrInputChannelForEventHandler(AnnotationRegistration $registration): string
    {
        $inputChannelName = self::getMessageClassFor($registration) ?? $registration->getAnnotationForMethod()->endpointId . ".target";

        Assert::notNull($inputChannelName, "Can't register {$registration}, lack of routing information. Have you forgot to type hint command/query/event or declare input channel name in annotation?");
        return $inputChannelName;
    }

    public static function getMessageClassOrInputChannel(AnnotationRegistration $registration): string
    {
        $inputChannelName = self::getMessageClassFor($registration) ?? $registration->getAnnotationForMethod()->inputChannelName;

        Assert::notNull($inputChannelName, "Can't register {$registration}, lack of routing information. Have you forgot to type hint command/query/event or declare input channel name in annotation?");
        return $inputChannelName;
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

        $inputChannelNames = [];

        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            $inputChannelName = self::getMessageChannelFor($registration);
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $inputChannelNames = $this->addUniqueChannelName($inputChannelName, $inputChannelNames, $registration->getAnnotationForMethod()->mustBeUnique);

            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));

            $handlerTargetChannel = $annotationForMethod->endpointId . ".target";
            $configuration->registerMessageHandler(
                BridgeBuilder::create()
                    ->withEndpointId($annotationForMethod->endpointId)
                    ->withInputChannelName(self::getMessageChannelFor($registration))
                    ->withOutputMessageChannel($handlerTargetChannel)
            );

            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, $handlerTargetChannel, $annotationForMethod->dropMessageOnNotFound, $inputChannelName . "." . $annotationForMethod->endpointId);
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $inputChannelName = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            $this->registerAggregateCommandHandler($configuration, $this->aggregateRepositoryReferenceNames, $registration, $inputChannelName, $registration->getAnnotationForMethod()->dropMessageOnNotFound, $registration->getAnnotationForMethod()->endpointId);
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            /** @var QueryHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $parameterConverterAnnotations = $annotation->parameterConverters;
            $parameterConverters = $parameterConverterAnnotationFactory->createParameterConvertersWithReferences($relatedClassInterface, $parameterConverterAnnotations, $registration, $annotation->ignoreMessage);


            $endpointId = $registration->getAnnotationForMethod()->endpointId;
            $inputChannelName = self::getMessageChannelFor($registration);
            $handledMessageClassName = self::getMessageClassFor($registration);
            $configuration->registerMessageHandler(
                AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith($registration->getClassName(), $registration->getMethodName(), TypeDescriptor::create($handledMessageClassName)->isIterable() ? null : $handledMessageClassName)
                    ->withInputChannelName($inputChannelName)
                    ->withOutputMessageChannel($registration->getAnnotationForMethod()->outputChannelName)
                    ->withEndpointId($endpointId)
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

            $inputChannelNames = $this->addUniqueChannelName($inputChannelName, $inputChannelNames, true);
        }

        foreach ($this->serviceCommandHandlersRegistrations as $registration) {
            $inputChannelName = self::getMessageChannelFor($registration);
            /** @var CommandHandler $annotationForMethod */
            $annotationForMethod = $registration->getAnnotationForMethod();
            $inputChannelNames = $this->addUniqueChannelName($inputChannelName, $inputChannelNames, $registration->getAnnotationForMethod()->mustBeUnique);

            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel(
                $inputChannelName
            ));

            $handlerTargetChannel = $annotationForMethod->endpointId . ".target";
            $configuration->registerMessageHandler(
                BridgeBuilder::create()
                    ->withEndpointId($annotationForMethod->endpointId)
                    ->withInputChannelName($inputChannelName)
                    ->withOutputMessageChannel($handlerTargetChannel)
            );

            $configuration->registerMessageHandler($this->createServiceActivator($handlerTargetChannel, $registration,$inputChannelName . "." . $annotationForMethod->endpointId));
        }
        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator(self::getMessageChannelFor($registration), $registration, $registration->getAnnotationForMethod()->endpointId));
            $inputChannelNames = $this->addUniqueChannelName(self::getMessageChannelFor($registration), $inputChannelNames, true);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $inputChannelName = AggregateMessagingModule::getMessageChannelForEventHandler($registration);
            $configuration->registerMessageHandler($this->createServiceActivator($inputChannelName, $registration, $registration->getAnnotationForMethod()->endpointId));
        }
    }

    /***
     * @param AnnotationRegistration $registration
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws AnnotationException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     * @throws MessagingException
     */
    public static function getMessageChannelFor(AnnotationRegistration $registration): string
    {
        /** @var CommandHandler|QueryHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();

        $inputChannel = property_exists($methodAnnotation, "inputChannelName") && $methodAnnotation->inputChannelName ? $methodAnnotation->inputChannelName : self::getMessageClassFor($registration);

        return $inputChannel ?? "";
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

    /**
     * @param Configuration $configuration
     * @param array $aggregateRepositoryReferenceNames
     * @param AnnotationRegistration $registration
     * @param string $inputChannelName
     *
     * @param bool $dropMessageOnNotFound
     *
     * @param string $endpointId
     * @return void
     * @throws AnnotationException
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws ReflectionException
     * @throws TypeDefinitionException
     */
    private function registerAggregateCommandHandler(Configuration $configuration, array $aggregateRepositoryReferenceNames, AnnotationRegistration $registration, string $inputChannelName, bool $dropMessageOnNotFound, string $endpointId): void
    {
        $parameterConverterAnnotationFactory = ParameterConverterAnnotationFactory::create();

        /** @var CommandHandler $annotation */
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
            AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith($registration->getClassName(), $registration->getMethodName(), TypeDescriptor::create($handledMessageClassName)->isIterable()  ? null : $handledMessageClassName)
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
     * @param string $channelName
     * @param array $inputChannelNames
     *
     * @param string $throwExceptionOnFound
     * @return array
     * @throws MessagingException
     */
    private function addUniqueChannelName(string $channelName, array $inputChannelNames, string $throwExceptionOnFound): array
    {
        if (in_array($channelName, $inputChannelNames) && $throwExceptionOnFound) {
            throw ConfigurationException::create("Trying to register Handler twice for input {$channelName}");
        }
        $inputChannelNames[] = $channelName;

        return $inputChannelNames;
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