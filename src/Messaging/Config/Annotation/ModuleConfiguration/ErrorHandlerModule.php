<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandler;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessageHeaders;

#[ModuleAnnotation]
class ErrorHandlerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "errorHandlerModule";

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
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        /** @var ErrorHandlerConfiguration $extensionObject */
        foreach ($extensionObjects as $extensionObject) {
            $errorHandler = ServiceActivatorBuilder::createWithDirectReference(
                new ErrorHandler($extensionObject->getDelayedRetryTemplate(), (bool)$extensionObject->getDeadLetterQueueChannel()), "handle"
            )
                ->withEndpointId("error_handler." . $extensionObject->getErrorChannelName())
                ->withInputChannelName($extensionObject->getErrorChannelName());
            if ($extensionObject->getDeadLetterQueueChannel()) {
                $errorHandler = $errorHandler->withOutputMessageChannel($extensionObject->getDeadLetterQueueChannel());
                $configuration
                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getDeadLetterQueueChannel()));
            }
            $configuration
                ->registerMessageHandler($errorHandler)
                ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getErrorChannelName()))
                ->registerMessageHandler(
                    RouterBuilder::createHeaderRouter(MessageHeaders::POLLED_CHANNEL_NAME)
                        ->withInputChannelName("ecotone.recoverability.reply")
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
}