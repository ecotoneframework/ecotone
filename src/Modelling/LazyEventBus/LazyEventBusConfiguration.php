<?php
declare(strict_types=1);


namespace Ecotone\Modelling\LazyEventBus;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Annotation\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInterceptor;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\Precedence;

#[ModuleAnnotation]
class LazyEventBusConfiguration implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService): static
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
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithObjectBuilder(
                LazyEventBusInterceptor::class,
                 new LazyEventBusAroundInterceptorBuilder($inMemoryEventStore),
                    "publish",
                    Precedence::LAZY_EVENT_PUBLICATION_PRECEDENCE,
                    LazyEventPublishing::class . "||" . AsynchronousRunningEndpoint::class
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
    public function getRelatedReferences(): array
    {
        return [];
    }
}