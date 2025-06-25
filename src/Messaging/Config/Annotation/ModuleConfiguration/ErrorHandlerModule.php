<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Config\Container\Reference;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\ReferenceBuilder;
use Ecotone\Messaging\Handler\Recoverability\DelayedRetryErrorHandler;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryRunner;
use Ecotone\Messaging\Handler\Router\HeaderRouter;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class ErrorHandlerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        if (! $this->hasErrorConfiguration($extensionObjects)) {
            $extensionObjects = [ErrorHandlerConfiguration::createDefault()];
        }
        $messagingConfiguration->registerServiceDefinition(RetryRunner::class, [new Reference(EcotoneClockInterface::class), new Reference(LoggingGateway::class)]);

        /** @var ErrorHandlerConfiguration $extensionObject */
        foreach ($extensionObjects as $index => $extensionObject) {
            if (! ($extensionObject instanceof ErrorHandlerConfiguration)) {
                continue;
            }

            $errorHandler = ServiceActivatorBuilder::createWithDefinition(
                new Definition(DelayedRetryErrorHandler::class, [
                    $extensionObject->getDelayedRetryTemplate(),
                    (bool)$extensionObject->getDeadLetterQueueChannel(),
                    Reference::to(LoggingGateway::class),
                ]),
                'handle',
            )
                ->withEndpointId('error_handler.' . $extensionObject->getErrorChannelName())
                ->withInputChannelName($extensionObject->getErrorChannelName())
                ->withMethodParameterConverters([
                    ReferenceBuilder::create('logger', LoggingGateway::class),
                ]);
            if ($extensionObject->getDeadLetterQueueChannel()) {
                $errorHandler = $errorHandler->withOutputMessageChannel($extensionObject->getDeadLetterQueueChannel());
                $messagingConfiguration
                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getDeadLetterQueueChannel()));
            }

            $messagingConfiguration
                ->registerMessageHandler($errorHandler)
                ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getErrorChannelName()))
                ->registerMessageHandler(
                    RouterBuilder::create(
                        new Definition(HeaderRouter::class, [MessageHeaders::POLLED_CHANNEL_NAME]),
                        $interfaceToCallRegistry->getFor(HeaderRouter::class, 'route')
                    )
                    ->withEndpointId('error_handler.' . $extensionObject->getErrorChannelName() . '.router')
                    ->withInputChannelName('ecotone.recoverability.reply')
                );
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof ErrorHandlerConfiguration;
    }

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
