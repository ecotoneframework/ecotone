<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Configuration;

/**
 * Interface ApplicationContextExtension
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ApplicationContextModuleExtension extends AnnotationModuleExtension
{
    /**
     * @param $messagingComponent
     * @return bool
     */
    public function canHandle($messagingComponent) : bool;

    /***
     * @param Configuration $configuration
     * @param object $messagingComponent
     */
    public function registerMessagingComponent(Configuration $configuration, $messagingComponent) : void;
}