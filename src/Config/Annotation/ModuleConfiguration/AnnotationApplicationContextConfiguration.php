<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfigurationAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassLocator;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ClassMetadataReader;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AnnotationApplicationContextConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfigurationAnnotation(moduleName="application-context-configuration")
 */
class AnnotationApplicationContextConfiguration implements AnnotationConfiguration
{
    /**
     * @var ConfigurationVariableRetrievingService
     */
    private $configurationVariableRetrievingService;
    /**
     * @var ClassLocator
     */
    private $classLocator;
    /**
     * @var ClassMetadataReader
     */
    private $classMetadataReader;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param ConfigurationVariableRetrievingService $configurationVariableRetrievingService
     * @param ClassLocator $classLocator
     * @param ClassMetadataReader $classMetadataReader
     */
    private function __construct(ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader)
    {
        $this->configurationVariableRetrievingService = $configurationVariableRetrievingService;
        $this->classLocator = $classLocator;
        $this->classMetadataReader = $classMetadataReader;
    }

    /**
     * @inheritDoc
     */
    public static function createAnnotationConfiguration(array $moduleConfigurationExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService, ClassLocator $classLocator, ClassMetadataReader $classMetadataReader): AnnotationConfiguration
    {
        return new self($configurationVariableRetrievingService, $classLocator, $classMetadataReader);
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        $annotationMessageEndpointConfigurationFinder = new AnnotationClassesWithMethodFinder($this->classLocator, $this->classMetadataReader);

        $classes = [];
        foreach ($annotationMessageEndpointConfigurationFinder->findFor(ApplicationContextAnnotation::class, MessagingComponentAnnotation::class) as $annotationRegistration) {
            if (!array_key_exists($annotationRegistration->getMessageEndpointClass(), $classes)) {
                $classToInstantiate = $annotationRegistration->getMessageEndpointClass();
                $classes[$annotationRegistration->getMessageEndpointClass()] = new $classToInstantiate();
            }

            $classToRun = $classes[$annotationRegistration->getMessageEndpointClass()];
            $messagingComponent = $classToRun->{$annotationRegistration->getMethodName()}();

            if ($messagingComponent instanceof MessageHandlerBuilder) {
                $configuration->registerMessageHandler($messagingComponent);
            } else if ($messagingComponent instanceof MessageChannelBuilder) {
                $configuration->registerMessageChannel($messagingComponent);
            } else {
                throw InvalidArgumentException::create(get_class($messagingComponent) . " is not known component to register");
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function postConfigure(ConfiguredMessagingSystem $configuredMessagingSystem): void
    {
        return;
    }
}