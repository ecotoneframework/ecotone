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
use SimplyCodedSoftware\Messaging\Annotation\MessageEndpoint;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ServiceActivator;
use SimplyCodedSoftware\Messaging\Channel\SimpleMessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ParameterConverterAnnotationFactory;
use SimplyCodedSoftware\Messaging\Config\Annotation\ModuleConfiguration\ServiceActivatorModule;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
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
    private $aggregateQueryHandlerRegistrations;
    /**
     * @var array|AnnotationRegistration[]
     */
    private $serviceEventHandlers;

    /**
     * CqrsMessagingModule constructor.
     *
     * @param ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory
     * @param AnnotationRegistration[]            $aggregateCommandHandlerRegistrations
     * @param AnnotationRegistration[]            $aggregateQueryHandlerRegistrations
     * @param AnnotationRegistration[]                               $serviceEventHandlers
     */
    private function __construct(
        ParameterConverterAnnotationFactory $parameterConverterAnnotationFactory,
        array $aggregateCommandHandlerRegistrations,
        array $aggregateQueryHandlerRegistrations,
        array $serviceEventHandlers
    )
    {
        $this->parameterConverterAnnotationFactory = $parameterConverterAnnotationFactory;
        $this->aggregateCommandHandlerRegistrations = $aggregateCommandHandlerRegistrations;
        $this->aggregateQueryHandlerRegistrations = $aggregateQueryHandlerRegistrations;
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
            $annotationRegistrationService->findRegistrationsFor(Aggregate::class, QueryHandler::class),
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

        foreach ($this->aggregateCommandHandlerRegistrations as $registration) {
            /** @var CommandHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $parameterConverters = $this->parameterConverterAnnotationFactory->createParameterConverters(
                InterfaceToCall::create($registration->getClassName(), $registration->getMethodName()),
                $annotation->parameterConverters
            );

            $endpointId = $registration->getAnnotationForMethod()->endpointId;
            $inputChannelName = self::getMessageChannelFor($registration);
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
                    $registration->getClassName()
                )
            );
        }

        foreach ($this->aggregateQueryHandlerRegistrations as $registration) {
            /** @var QueryHandler $annotation */
            $annotation = $registration->getAnnotationForMethod();

            $parameterConverters = $this->parameterConverterAnnotationFactory->createParameterConverters(
                InterfaceToCall::create($registration->getClassName(), $registration->getMethodName()),
                $annotation->parameterConverters
            );


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
                    $registration->getClassName()
                )
            );
        }

        foreach ($this->serviceEventHandlers as $serviceEventHandler) {
            /** @var ServiceActivator $annotation */
            $annotation = $serviceEventHandler->getAnnotationForMethod();
            $configuration->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($annotation->inputChannelName));
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
}