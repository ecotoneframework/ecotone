<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Annotation\Extension;
use Ecotone\Messaging\Config\Annotation\AnnotationModuleExtension;

/**
 * Class ExampleModuleConfigurationExtension
 * @package Test\Ecotone\Messaging\Fixture\Annotation\ModuleConfiguration
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