<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandler;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ErrorHandlerModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "errorHandlerModule";

    private function __construct()
    {
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService)
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        /** @var ErrorHandlerConfiguration $extensionObject */
        foreach ($extensionObjects as $extensionObject) {
            $errorHandler = ServiceActivatorBuilder::createWithDirectReference(
                new ErrorHandler($extensionObject->getRetryTemplate()), "handle"
            )
                ->withEndpointId("error_handler." . $extensionObject->getErrorChannelName())
                ->withInputChannelName($extensionObject->getErrorChannelName());
            if ($extensionObject->getDeadLetterQueueChannel()) {
                $errorHandler = $errorHandler->withOutputMessageChannel($extensionObject->getDeadLetterQueueChannel());
                $configuration
                    ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getDeadLetterQueueChannel()));
            }
            $configuration->registerMessageHandler($errorHandler)
                ->registerDefaultChannelFor(SimpleMessageChannelBuilder::createPublishSubscribeChannel($extensionObject->getErrorChannelName()));
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