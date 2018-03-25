<?php
declare(strict_types=1);

namespace Fixture\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;

/**
 * Class ExampleModuleConfigurationExtension
 * @package Fixture\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleExtensionAnnotation()
 */
class ExampleModuleConfigurationExtension implements AnnotationModuleExtension
{
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModuleExtension
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        // TODO: Implement getConfigurationVariables() method.
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        // TODO: Implement getRequiredReferences() method.
    }


}