<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
/**
 * licence Apache-2.0
 */
class RequiredConsumersModule extends NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @var string[]
     */
    private array $consumerIds = [];

    /**
     * RequiredConsumersModule constructor.
     *
     * @param string[] $consumerIds
     */
    private function __construct(array $consumerIds)
    {
        $this->consumerIds = $consumerIds;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationFinder $annotationRegistrationService, InterfaceToCallRegistry $interfaceToCallRegistry): static
    {
        $annotationRegistrations = $annotationRegistrationService->findAnnotatedMethods(MessageConsumer::class);

        return new self(
            array_map(
                function (AnnotatedFinding $annotationRegistration) {
                    /** @var MessageConsumer $annotationForMethod */
                    $annotationForMethod = $annotationRegistration->getAnnotationForMethod();

                    return $annotationForMethod->getEndpointId();
                },
                $annotationRegistrations
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $messagingConfiguration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->consumerIds as $consumerId) {
            $messagingConfiguration->requireConsumer($consumerId);
        }
    }

    /**
     * @inheritDoc
     */
    public function canHandle($extensionObject): bool
    {
        return false;
    }

    public function getModulePackageName(): string
    {
        return ModulePackageList::CORE_PACKAGE;
    }
}
