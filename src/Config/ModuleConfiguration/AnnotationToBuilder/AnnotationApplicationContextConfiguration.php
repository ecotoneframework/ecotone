<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;

/**
 * Class AnnotationApplicationContextConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class AnnotationApplicationContextConfiguration extends BaseAnnotationConfiguration
{
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