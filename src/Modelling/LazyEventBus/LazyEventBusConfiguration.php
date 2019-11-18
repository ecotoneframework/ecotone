<?php
declare(strict_types=1);


namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class LazyEventBusConfiguration
 * @package Ecotone\Modelling\LazyEventBus
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class LazyEventBusConfiguration implements AnnotationModule
{
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
        return "lazyEventBusModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
    {
        $inMemoryEventStore = new InMemoryEventStore();

        $configuration->registerMessageHandler(
            ServiceActivatorBuilder::createWithDirectReference(
                $inMemoryEventStore,
                "enqueue"
            )
                ->withInputChannelName(LazyEventBus::CHANNEL_NAME)
        );
        $configuration
            ->registerAfterMethodInterceptor(
                MethodInterceptor::create(
                    "ecotoneLazyEventBus",
                    InterfaceToCall::create(LazyEventBusInterceptor::class, "publish"),
                    new LazyEventBusInterceptorBuilder($inMemoryEventStore),
                    5,
                    "@(" . LazyEventPublishing::class . ")"
                )
            );
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
    public function getRequiredReferences(): array
    {
        return [];
    }
}