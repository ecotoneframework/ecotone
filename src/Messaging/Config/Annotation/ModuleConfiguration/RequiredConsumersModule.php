<?php

namespace Ecotone\Messaging\Config\Annotation\ModuleConfiguration;

use Ecotone\AnnotationFinder\AnnotatedFinding;
use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Annotation\MessageConsumer;
use Ecotone\Messaging\Annotation\MessageEndpoint;
use Ecotone\Messaging\Annotation\ModuleAnnotation;
use Ecotone\Messaging\Config\Annotation\AnnotationModule;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistration;
use Ecotone\Messaging\Config\Annotation\AnnotationRegistrationService;
use Ecotone\Messaging\Config\Configuration;
use Ecotone\Messaging\Config\ModuleReferenceSearchService;

/**
 * Class BasicMessagingConfiguration
 * @package Ecotone\Messaging\Config\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
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
    public static function create(AnnotationFinder $annotationRegistrationService): \Ecotone\Messaging\Config\Annotation\AnnotationModule
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
    public function getName(): string
    {
        return "requiredConsumersModule";
    }

    /**
     * @inheritDoc
     */
    public function prepare(Configuration $configuration, array $extensionObjects, ModuleReferenceSearchService $moduleReferenceSearchService): void
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