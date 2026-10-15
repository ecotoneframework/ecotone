<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\DelayedRetry;
use Ecotone\Api\Attribute\ErrorChannel;
use Ecotone\Api\Attribute\ModuleAnnotation;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Gateway\EcotoneClockInterface;
use Ecotone\Api\Messaging\MessageHeaders;
use Ecotone\Messaging\Config\Annotation\AnnotatedDefinitionReference;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Recoverability\DelayedRetryErrorHandler;
use Ecotone\Messaging\Handler\Recoverability\RetryRunner;
use Ecotone\Messaging\Handler\Router\HeaderRouter;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
final class ErrorHandlerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @param ErrorHandlerConfiguration[] $perHandlerRetryConfigurations
     */
    private function __construct(private array $perHandlerRetryConfigurations)
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $perHandlerRetryConfigurations = [];

        foreach ($annotationRegistrationService->findAnnotatedMethods(DelayedRetry::class) as $delayedRetryMethod) {
            if (! $delayedRetryMethod->hasMethodAnnotation(\Ecotone\Messaging\Attribute\MessageConsumer::class)) {
                continue;
            }
            throw ConfigurationException::create(
                ErrorChannelExceptionMessages::delayedRetryOnInboundChannelAdapter($delayedRetryMethod->getClassName(), $delayedRetryMethod->getMethodName())
            );
        }

        $endpointMethods = $annotationRegistrationService->findAnnotatedMethods(\Ecotone\Messaging\Attribute\EndpointAnnotation::class);
        $asynchronousMethods = $annotationRegistrationService->findAnnotatedMethods(Asynchronous::class);

        foreach ($asynchronousMethods as $asynchronousMethod) {
            /** @var Asynchronous $asyncAnnotation */
            $asyncAnnotation = $asynchronousMethod->getAnnotationForMethod();

            $delayedRetry = null;
            $hasErrorChannel = false;
            foreach ($asyncAnnotation->asynchronousExecution as $endpointAnnotation) {
                if ($endpointAnnotation instanceof DelayedRetry) {
                    $delayedRetry = $endpointAnnotation;
                } elseif ($endpointAnnotation instanceof ErrorChannel) {
                    $hasErrorChannel = true;
                }
            }
            if ($delayedRetry === null) {
                continue;
            }

            foreach ($endpointMethods as $endpoint) {
                if ($endpoint->getClassName() !== $asynchronousMethod->getClassName()
                    || $endpoint->getMethodName() !== $asynchronousMethod->getMethodName()) {
                    continue;
                }

                /** @var \Ecotone\Messaging\Attribute\EndpointAnnotation $endpointAttribute */
                $endpointAttribute = $endpoint->getAnnotationForMethod();
                $handlerEndpointId = $endpointAttribute->getEndpointId();

                if ($hasErrorChannel) {
                    throw ConfigurationException::create(
                        ErrorChannelExceptionMessages::errorChannelAndDelayedRetryMutuallyExclusiveOnHandler($handlerEndpointId)
                    );
                }

                $generatedErrorChannelName = DelayedRetry::generateChannelName($handlerEndpointId);
                $retryTemplateBuilder = new RetryTemplateBuilder(
                    $delayedRetry->initialDelayInMilliseconds,
                    $delayedRetry->multiplier,
                    $delayedRetry->maxDelayInMilliseconds,
                    $delayedRetry->maxRetries,
                );

                $perHandlerRetryConfigurations[] = self::buildErrorHandlerConfig($generatedErrorChannelName, $retryTemplateBuilder, $delayedRetry->deadLetterChannel);
                break;
            }
        }

        $gatewayInterfacesWithDelayedRetry = $annotationRegistrationService->findAnnotatedClasses(DelayedRetry::class);
        foreach ($gatewayInterfacesWithDelayedRetry as $gatewayInterfaceFqn) {
            /** @var DelayedRetry $delayedRetry */
            $delayedRetry = AnnotatedDefinitionReference::getSingleAnnotationForClass($annotationRegistrationService, $gatewayInterfaceFqn, DelayedRetry::class);

            $errorChannelOnGateway = $annotationRegistrationService->findAnnotatedClasses(ErrorChannel::class);
            if (in_array($gatewayInterfaceFqn, $errorChannelOnGateway, true)) {
                throw ConfigurationException::create(
                    ErrorChannelExceptionMessages::errorChannelAndDelayedRetryMutuallyExclusiveOnGateway($gatewayInterfaceFqn)
                );
            }

            $generatedErrorChannelName = DelayedRetry::generateGatewayChannelName($gatewayInterfaceFqn);
            $retryTemplateBuilder = new RetryTemplateBuilder(
                $delayedRetry->initialDelayInMilliseconds,
                $delayedRetry->multiplier,
                $delayedRetry->maxDelayInMilliseconds,
                $delayedRetry->maxRetries,
            );
            $perHandlerRetryConfigurations[] = self::buildErrorHandlerConfig($generatedErrorChannelName, $retryTemplateBuilder, $delayedRetry->deadLetterChannel);
        }

        return new self($perHandlerRetryConfigurations);
    }

    private static function buildErrorHandlerConfig(string $errorChannelName, RetryTemplateBuilder $retryTemplateBuilder, ?string $deadLetterChannel): ErrorHandlerConfiguration
    {
        return $deadLetterChannel !== null
            ? ErrorHandlerConfiguration::createWithDeadLetterChannel($errorChannelName, $retryTemplateBuilder, $deadLetterChannel)
            : ErrorHandlerConfiguration::create($errorChannelName, $retryTemplateBuilder);
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (! $this->hasErrorConfiguration($extensionObjects)) {
            $extensionObjects = [ErrorHandlerConfiguration::createDefault()];
        }
        $extensionObjects = array_merge($extensionObjects, $this->perHandlerRetryConfigurations);

        $messagingConfiguration->registerServiceDefinition(RetryRunner::class, [new Reference(EcotoneClockInterface::class), new Reference(LoggingGateway::class)]);

        $hasAnyErrorHandlerConfiguration = false;

        /** @var ErrorHandlerConfiguration $extensionObject */
        foreach ($extensionObjects as $index => $extensionObject) {
            if (! ($extensionObject instanceof ErrorHandlerConfiguration)) {
                continue;
            }
            $hasAnyErrorHandlerConfiguration = true;

            $errorHandler = ServiceActivatorBuilder::createWithDefinition(
                new Definition(DelayedRetryErrorHandler::class, [
                    $extensionObject->delayedRetryTemplate,
                    (bool)$extensionObject->deadLetterChannel,
                    Reference::to(LoggingGateway::class),
                ]),
                'handle',
            )
                ->withEndpointId('error_handler.' . $extensionObject->getErrorChannelName())
                ->withInputChannelName($extensionObject->getErrorChannelName())
                ->withMethodParameterConverters([
                    ReferenceBuilder::create('logger', LoggingGateway::class),
                ]);
            if ($extensionObject->deadLetterChannel) {
                $errorHandler = $errorHandler->withOutputMessageChannel($extensionObject->deadLetterChannel);
                $messagingConfiguration
                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->deadLetterChannel));
            }

            $messagingConfiguration
                ->registerMessageHandler($errorHandler)
                ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getErrorChannelName()));
        }

        if ($hasAnyErrorHandlerConfiguration) {
            $messagingConfiguration->registerMessageHandler(
                RouterBuilder::create(
                    new Definition(HeaderRouter::class, [MessageHeaders::POLLED_CHANNEL_NAME]),
                    $interfaceToCallRegistry->getFor(HeaderRouter::class, 'route')
                )
                ->withEndpointId('error_handler.recoverability.reply.router')
                ->withInputChannelName('ecotone.recoverability.reply')
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }

    private function hasErrorConfiguration(array $extensionObjects): bool
    {
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof ErrorHandlerConfiguration) {
                return true;
            }
        }

        return false;
    }
}
