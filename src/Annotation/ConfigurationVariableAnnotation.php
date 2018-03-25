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
     * @var bool
     */
    public $isRequired = true;
    /**
     * @var string
     * @Required()
     */
    public $description = "";
}