<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;

/**
 * Class AnnotationApplicationContextConfiguration
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationToBuilder
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ModuleAnnotation()
 */
class ApplicationContextModule extends NoExternalConfigurationModule implements AnnotationModule
{
    public const MODULE_NAME = "applicationContextModule";

    /**
     * @var AnnotationRegistrationService
     */
    private $annotationRegistrationService;

    /**
     * AnnotationGatewayConfiguration constructor.
     * @param AnnotationRegistrationService $annotationRegistrationService
     */
    private function __construct(AnnotationRegistrationService $annotationRegistrationService)
    {
        $this->annotationRegistrationService = $annotationRegistrationService;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self($annotationRegistrationService);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return self::MODULE_NAME;
    }

    /**
     * @inheritDoc
     */
    public function registerWithin(Configuration $configuration, array $moduleExtensions, ConfigurationVariableRetrievingService $configurationVariableRetrievingService): void
    {
        $classes = [];
        foreach ($this->annotationRegistrationService->findRegistrationsFor(ApplicationContextAnnotation::class, MessagingComponentAnnotation::class) as $annotationRegistration) {
            if (!array_key_exists($annotationRegistration->getClassWithAnnotation(), $classes)) {
                $classToInstantiate = $annotationRegistration->getClassWithAnnotation();
                $classes[$annotationRegistration->getClassWithAnnotation()] = new $classToInstantiate();
            }

            $classToRun = $classes[$annotationRegistration->getClassWithAnnotation()];
            $messagingComponent = $classToRun->{$annotationRegistration->getMethodName()}();

            if ($messagingComponent instanceof MessageHandlerBuilder) {
                $configuration->registerMessageHandler($messagingComponent);
            } else if ($messagingComponent instanceof MessageChannelBuilder) {
                $configuration->registerMessageChannel($messagingComponent);
            } else if ($messagingComponent instanceof GatewayBuilder) {
                $configuration->registerGatewayBuilder($messagingComponent);
            } else {
                throw ConfigurationException::create(get_class($messagingComponent) . " is not known component to register");
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