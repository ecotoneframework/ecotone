<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Attribute\MessageConsumer;
use Ecotone\Messaging\Attribute\MessageEndpoint;
use Ecotone\Messaging\Attribute\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;

#[ModuleAnnotation]
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
        $annotationRegistrations = $annotationRegistrationService->findAnnotatedMethods( MessageConsumer::class);

        return new self(
            array_map(
                function (AnnotatedFinding $annotationRegistration) {
                    /** @var MessageConsumer $annotationForMethod */
                    $annotationForMethod = $annotationRegistration->getAnnotationForMethod();

                    return $annotationForMethod->getEndpointId();
                }, $annotationRegistrations
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService, InterfaceToCallRegistry $interfaceToCallRegistry): void
    {
        foreach ($this->consumerIds as $consumerId) {
            $configuration->requireConsumer($consumerId);
        }
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