<?php
declare(strict_types=1);

namespace Fixture\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleExtensionAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariable;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExampleModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 * @ModuleExtensionAnnotation()
 */
class ExampleModuleAndModuleExtensionConfiguration implements \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule, AnnotationModuleExtension
{
    private function __construct()
    {
    }

    public static function createEmpty() : self
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "exampleModule";
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
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        // TODO: Implement getRequiredReferences() method.
    }

    /**
     * @inheritDoc
     */
    public static function create(\SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService $annotationRegistrationService): \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule
    {
        return new self();
    }

    /**
     * @inheritDoc
     */
    public function configure(\SimplyCodedSoftware\IntegrationMessaging\Config\Configuration $configuration, array $moduleExtensions, \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
    {
        // TODO: Implement registerWithin() method.
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(\SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        // TODO: Implement postConfigure() method.
    }
}