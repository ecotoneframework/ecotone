<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ConfigurationVariableAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class ConfigurationVariableAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $variableName;
    /**
     * @var string
     */
    public $defaultValue = "";
    /**
     * Make sense only if no default value was set
     *
     * @var string
     */
    public $isRequired = false;
    /**
     * @var string
     */
    public $description = "";
}