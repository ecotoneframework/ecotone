<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\DomainModel\LazyEventBus;

use SimplyCodedSoftware\DomainModel\EventBus;
use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Extension;
use SimplyCodedSoftware\Messaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ModuleReferenceSearchService;
use SimplyCodedSoftware\Messaging\Config\RequiredReference;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorObjectBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use SimplyCodedSoftware\Messaging\Handler\ReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;

/**
 * Class LazyEventBusConfiguration
 * @package SimplyCodedSoftware\DomainModel\LazyEventBus
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