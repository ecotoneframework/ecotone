<?php
declare(strict_types=1);

namespace Fixture\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariable;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\RequiredReference;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

/**
 * Class ExampleModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ExampleModuleConfiguration implements \SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule
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
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver) : void
    {
        // TODO: Implement preConfigure() method.
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
    public function registerWithin(\SimplyCodedSoftware\IntegrationMessaging\Config\Configuration $configuration, array $moduleExtensions, \SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ReferenceSearchService $referenceSearchService): void
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