<?php

namespace SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\ModuleConfiguration;
use SimplyCodedSoftware\Messaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\Messaging\Config\Configuration;
use SimplyCodedSoftware\Messaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\Messaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;

/**
 * Class AnnotationApplicationContextConfiguration
 * @package SimplyCodedSoftware\Messaging\Config\ModuleConfiguration\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleConfiguration()
 */
class AnnotationApplicationContextConfiguration extends BaseAnnotationConfiguration
{
    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration): void
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