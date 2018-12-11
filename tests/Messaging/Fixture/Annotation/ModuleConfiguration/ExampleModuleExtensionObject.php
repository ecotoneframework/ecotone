<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration;

use SimplyCodedSoftware\Messaging\Annotation\ApplicationContext;
use SimplyCodedSoftware\Messaging\Annotation\Extension;
use SimplyCodedSoftware\Messaging\Config\Annotation\AnnotationModuleExtension;

/**
 * Class ExampleModuleConfigurationExtension
 * @package Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\ModuleConfiguration
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