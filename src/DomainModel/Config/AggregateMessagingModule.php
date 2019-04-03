<?php

namespace SimplyCodedSoftware\DomainModel\Config;

use SimplyCodedSoftware\DomainModel\AggregateMessage;
use SimplyCodedSoftware\DomainModel\AggregateMessageConversionServiceBuilder;
use SimplyCodedSoftware\DomainModel\AggregateMessageHandlerBuilder;
use SimplyCodedSoftware\DomainModel\AggregateRepositoryFactory;
use SimplyCodedSoftware\DomainModel\Annotation\Aggregate;
use SimplyCodedSoftware\DomainModel\Annotation\CommandHandler;
use SimplyCodedSoftware\DomainModel\Annotation\EventHandler;
use SimplyCodedSoftware\DomainModel\Annotation\QueryHandler;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Payload;
use SimplyCodedSoftware\Messaging\Annotation\Parameter\Reference;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfigurationException;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Handler\TypeDescriptor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class IntegrationMessagingCqrsModule
 * @package SimplyCodedSoftware\DomainModel\Config
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
     * CqrsMessagingModule constructor.
     *
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     * @param AnnotationRegistration[]            $aggregateCommandHandlerRegistrations
     * @param AnnotationRegistration[]            $serviceCommandHandlersRegistrations
     * @param AnnotationRegistration[]            $aggregateQueryHandlerRegistrations
     * @param AnnotationRegistration[]            $serviceQueryHandlerRegistrations
     * @param AnnotationRegistration[]            $aggregateEventHandlers
     * @param AnnotationRegistration[]            $serviceEventHandlers
     */
    private function __construct(
        ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory,
        array $aggregateCommandHandlerRegistrations,
        array $serviceCommandHandlersRegistrations,
        array $aggregateQueryHandlerRegistrations,
        array $serviceQueryHandlerRegistrations,
        array $aggregateEventHandlers,
        array $serviceEventHandlers
    )
    {
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
        $this->aggregateCommandHandlerRegistrations = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlerRegistrations = $aggregateQueryHandlerRegistrations;
        $this->serviceCommandHandlersRegistrations = $serviceCommandHandlersRegistrations;
        $this->serviceQueryHandlerRegistrations = $serviceQueryHandlerRegistrations;
        $this->aggregateEventHandlers = $aggregateEventHandlers;
        $this->serviceEventHandlers = $serviceEventHandlers;
    }

    /**
     * In here we should provide messaging component for module
     *
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self(
            ParameterConverterAnnotationFactory::create(),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, CommandHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, QueryHandler::class),
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, EventHandler::class),
            $annotationRegistrationService->findRegistrationsFor(MessageEndpoint::class, EventHandler::class)
        );
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
    public function getRequiredReferences(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof AggregateRepositoryFactory;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $moduleExtensions): void
    {
        /** @var AggregateRepositoryFactory $moduleExtension */
        foreach ($moduleExtensions as $moduleExtension) {
            $configuration->requireReferences($moduleExtension->getRequiredReferences());
        }

        $inputChannelNames = [];

        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            $inputChannelName = self::getMessageChannelFor($registration);
            $this->registerAggregateCommandHandler($configuration, $moduleExtensions, $registration, $inputChannelName);

            $inputChannelNames = $this->addUniqueChannelName($inputChannelName, $inputChannelNames);
        }

        foreach ($this->aggregateEventHandlers as $registration) {
            $inputChannelName = self::getMessageChannelFor($registration);
            $this->registerAggregateCommandHandler($configuration, $moduleExtensions, $registration, $inputChannelName);

            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($inputChannelName));
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            /** @var QueryHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $relatedClassInterface = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
            $parameterConverterAnnotations = $annotation->parameterConverters;
            $parameterConverters = $this->getParameterConverters($relatedClassInterface, $parameterConverterAnnotations);


            $endpointId = $registration->getAnnotationForMethod()->endpointId;
            $inputChannelName = self::getMessageChannelFor($registration);
            $handledMessageClassName = self::getMessageClassFor($registration);
            $configuration->registerMessageHandler(
                AggregateMessageHandlerBuilder::createAggregateQueryHandlerWith($registration->getClassName(), $registration->getMethodName(), $handledMessageClassName)
                    ->withInputChannelName($inputChannelName)
                    ->withOutputMessageChannel($registration->getAnnotationForMethod()->outputChannelName)
                    ->withEndpointId($endpointId)
                    ->withAggregateRepositoryFactories($moduleExtensions)
                    ->withMethodParameterConverters($parameterConverters)
            );

            $configuration->registerBeforeMethodInterceptor(
                MethodInterceptor::create(
                    "",
                    AggregateMessageConversionServiceBuilder::createWith($handledMessageClassName),
                    AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                    $registration->getClassName() . "::" . $registration->getMethodName()
                )
            );

            $inputChannelNames = $this->addUniqueChannelName($inputChannelName, $inputChannelNames);
        }

        foreach ($this->serviceCommandHandlersRegistrations as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator($registration));
            $inputChannelNames = $this->addUniqueChannelName(self::getMessageChannelFor($registration), $inputChannelNames);
        }
        foreach ($this->serviceQueryHandlerRegistrations as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator($registration));
            $inputChannelNames = $this->addUniqueChannelName(self::getMessageChannelFor($registration), $inputChannelNames);
        }
        foreach ($this->serviceEventHandlers as $registration) {
            $configuration->registerMessageHandler($this->createServiceActivator($registration));
            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel(self::getMessageChannelFor($registration)));
        }
    }

    /***
     * @param AnnotationRegistration $registration
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function getMessageChannelFor(AnnotationRegistration $registration): string
    {
        /** @var CommandHandler|QueryHandler $methodAnnotation */
        $methodAnnotation = $registration->getAnnotationForMethod();

        return $methodAnnotation->inputChannelName ? $methodAnnotation->inputChannelName : self::getMessageClassFor($registration);
    }

    /**
     * @param AnnotationRegistration $registration
     *
     * @return string
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function getMessageClassFor(AnnotationRegistration $registration)
    {
        $interfaceToCall = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $messageClassName = $registration->getAnnotationForMethod()->messageClassName;
        if ($messageClassName) {
            return (TypeDescriptor::create($messageClassName)->getTypeHint());
        }

        return $interfaceToCall->getFirstParameterTypeHint();
    }

    /**
     * @param InterfaceToCall $relatedClassInterface
     * @param array           $parameterConverterAnnotations
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function getParameterConverters(InterfaceToCall $relatedClassInterface, array $parameterConverterAnnotations): array
    {
        if ($relatedClassInterface->hasMoreThanOneParameter() && !$parameterConverterAnnotations) {
            $interfaceParameters = $relatedClassInterface->getInterfaceParameters();

            for ($index = 0; $index < count($interfaceParameters); $index++) {
                if ($index == 0) {
                    $payload                         = new Payload();
                    $payload->parameterName          = $interfaceParameters[$index]->getName();
                    $parameterConverterAnnotations[] = $payload;
                    continue;
                }

                $reference                       = new Reference();
                $reference->parameterName        = $interfaceParameters[$index]->getName();
                $reference->referenceName        = $interfaceParameters[$index]->getTypeHint();
                $parameterConverterAnnotations[] = $reference;
            }
        }

        $parameterConverters = $this->parameterConverterAnnotationFactory->createParameterConverters($relatedClassInterface, $parameterConverterAnnotations);

        return $parameterConverters;
}

    /**
     * @param AnnotationRegistration $registration
     *
     * @return ServiceActivatorBuilder
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function createServiceActivator(AnnotationRegistration $registration): ServiceActivatorBuilder
    {
        $inputChannelName = self::getMessageChannelFor($registration);
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $this->getParameterConverters($relatedClassInterface, $parameterConverterAnnotations);

        $messageHandlerBuilder = ServiceActivatorBuilder::create($registration->getReferenceName(), $registration->getMethodName())
            ->withInputChannelName($inputChannelName)
            ->withEndpointId($annotation->endpointId)
            ->withMethodParameterConverters($parameterConverters);

        return $messageHandlerBuilder;
}

    /**
     * @param string $channelName
     * @param array  $inputChannelNames
     *
     * @return array
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function addUniqueChannelName(string $channelName, array $inputChannelNames): array
    {
        if (in_array($channelName, $inputChannelNames)) {
            throw ConfigurationException::create("Trying to register Command Handler twice for input {$channelName}");
        }
        $inputChannelNames[] = $channelName;

        return $inputChannelNames;
}

    /**
     * @param Configuration          $configuration
     * @param array                  $moduleExtensions
     * @param AnnotationRegistration $registration
     * @param string                 $inputChannelName
     *
     * @return array
     * @throws InvalidArgumentException
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     * @throws \SimplyCodedSoftware\Messaging\Handler\TypeDefinitionException
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    private function registerAggregateCommandHandler(Configuration $configuration, array $moduleExtensions, AnnotationRegistration $registration, string $inputChannelName): void
    {
        /** @var CommandHandler $annotation */
        $annotation = $registration->getAnnotationForMethod();

        $relatedClassInterface         = InterfaceToCall::create($registration->getClassName(), $registration->getMethodName());
        $parameterConverterAnnotations = $annotation->parameterConverters;
        $parameterConverters           = $this->getParameterConverters($relatedClassInterface, $parameterConverterAnnotations);

        $endpointId              = $registration->getAnnotationForMethod()->endpointId;
        $handledMessageClassName = self::getMessageClassFor($registration);
        $configuration->registerMessageHandler(
            AggregateMessageHandlerBuilder::createAggregateCommandHandlerWith($registration->getClassName(), $registration->getMethodName(), $handledMessageClassName)
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointId)
                ->withAggregateRepositoryFactories($moduleExtensions)
                ->withMethodParameterConverters($parameterConverters)
        );

        $configuration->registerBeforeMethodInterceptor(
            \SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor::create(
                "",
                AggregateMessageConversionServiceBuilder::createWith($handledMessageClassName),
                AggregateMessage::BEFORE_CONVERTER_INTERCEPTOR_PRECEDENCE,
                $registration->getClassName() . "::" . $registration->getMethodName()
            )
        );
    }
}