<?php
declare(strict_types=1);

namespace Fixture\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\IntegrationMessaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\IntegrationMessaging\Annotation\Extension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationModuleExtension;
use SimplyCodedSoftware\IntegrationMessaging\Config\Annotation\AnnotationRegistrationService;

/**
 * Class ExampleModuleConfigurationExtension
 * @package Fixture\Annotation\ModuleConfiguration
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @ApplicationContext()
 */
class ExampleModuleExtensionObject
{
    /**
     * @return \stdClass
     * @Extension()
     */
    public function extensionObject() : \stdClass
    {
        return new \stdClass();
    }
}