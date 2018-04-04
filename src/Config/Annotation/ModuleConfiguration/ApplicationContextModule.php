<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContextAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\MessagingComponentAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\ModuleAnnotation;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptor;
use SimplyCodedSoftware\IntegrationMessaging\Channel\ChannelInterceptorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageChannelBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistration;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationException;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationVariableRetrievingService;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfiguredMessagingSystem;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Gateway\GatewayBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\MessageHandlerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ReferenceSearchService;

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
     * @var AnnotationRegistration[]
     */
    private $messagingComponentsRegistrations;

    /**
     * AnnotationGatewayConfiguration constructor.
     *
     * @param AnnotationRegistration[] $messagingComponents
     */
    private function __construct(array $messagingComponents)
    {
        $this->messagingComponentsRegistrations = $messagingComponents;
    }

    /**
     * @inheritDoc
     */
    public static function create(AnnotationRegistrationService $annotationRegistrationService): AnnotationModule
    {
        return new self($annotationRegistrationService->findRegistrationsFor(ApplicationContextAnnotation::class, MessagingComponentAnnotation::class));
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
    public function prepare(Configuration $configuration, array $moduleExtensions, ConfigurationObserver $configurationObserver) : void
    {
        $classes = [];
        foreach ($this->messagingComponentsRegistrations as $annotationRegistration) {
            if (!array_key_exists($annotationRegistration->getClassWithAnnotation(), $classes)) {
                $classToInstantiate = $annotationRegistration->getClassWithAnnotation();
                $classes[$annotationRegistration->getClassWithAnnotation()] = new $classToInstantiate();
            }

            $classToRun = $classes[$annotationRegistration->getClassWithAnnotation()];
            $messagingComponent = $classToRun->{$annotationRegistration->getMethodName()}();

            if(!is_array($messagingComponent)) {
                $this->registerMessagingComponent($configuration, $messagingComponent);
                continue;
            }

            foreach ($messagingComponent as $singleMessagingCompoenent) {
                $this->registerMessagingComponent($configuration, $singleMessagingCompoenent);
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

    /**
     * @param Configuration $configuration
     * @param object $messagingComponent
     *
     * @throws ConfigurationException
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    private function registerMessagingComponent(Configuration $configuration, $messagingComponent): void
    {
        if ($messagingComponent instanceof ChannelInterceptorBuilder) {
            $configuration->registerChannelInterceptor($messagingComponent);
        } else if ($messagingComponent instanceof MessageHandlerBuilder) {
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