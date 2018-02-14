<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ModuleConfigurationAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Config\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class ModuleConfigurationAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $moduleName;
    /**
     * @var ConfigurationVariableAnnotation[]
     */
    public $variables;
}