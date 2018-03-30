<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModule;
use SimplyCodedSoftware\IntegrationMessaging\Config\ConfigurationObserver;
use SimplyCodedSoftware\IntegrationMessaging\Config\ModuleExtension;

/**
 * Class NoExternalConfigurationModule
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @internal
 */
abstract class NoExternalConfigurationModule implements AnnotationModule
{
    /**
     * @inheritDoc
     */
    public function getConfigurationVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function preConfigure(array $moduleExtensions, ConfigurationObserver $configurationObserver) : void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function getRequiredReferences(): array
    {
        return [];
    }
}