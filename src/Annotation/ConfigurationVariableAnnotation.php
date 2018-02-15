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
     * @var string If default value is not set, then variable is required
     */
    public $defaultValue = "";
    /**
     * @var string
     * @Required()
     */
    public $description = "";
}