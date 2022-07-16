<?php

namespace Ecotone\Dbal\Deduplication;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Dbal\Configuration\DbalConfiguration;
use Ecotone\Dbal\Configuration\DbalModule;
use Ecotone\Messaging\Attribute\AsynchronousRunningEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Precedence;
use Ecotone\Messaging\Scheduling\EpochBasedClock;
use Enqueue\Dbal\DbalConnectionFactory;

#[ModuleAnnotation]
class DeduplicationModule implements AnnotationModule
{
    public const REMOVE_MESSAGE_AFTER_7_DAYS = 1000 * 60 * 60 * 24 * 7;

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
        $isDeduplicatedEnabled = false;
        $connectionFactory     = DbalConnectionFactory::class;
        foreach ($extensionObjects as $extensionObject) {
            if ($extensionObject instanceof DbalConfiguration) {
                if (! $extensionObject->isDeduplicatedEnabled()) {
                    return;
                }

                $connectionFactory     = $extensionObject->getDeduplicationConnectionReference();
                $isDeduplicatedEnabled = true;
            }
        }

        if (! $isDeduplicatedEnabled) {
            return;
        }

        $configuration
            ->registerAroundMethodInterceptor(
                AroundInterceptorReference::createWithDirectObjectAndResolveConverters(
                    new DeduplicationInterceptor(
                        $connectionFactory,
                        new EpochBasedClock(),
                        self::REMOVE_MESSAGE_AFTER_7_DAYS
                    ),
                    'deduplicate',
                    Precedence::DATABASE_TRANSACTION_PRECEDENCE + 100,
                    AsynchronousRunningEndpoint::class
                )
            );
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return $extensionObject instanceof DbalConfiguration;
    }

    public function getModuleExtensions(array $serviceExtensions): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelatedReferences(): array
    {
        return [];
    }

    public function getModulePackageName(): string
    {
        return DbalModule::NAME;
    }
}
