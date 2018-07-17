<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageToStaticValueParameterAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class Value
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var mixed
     * @Required()
     */
    public $value;
}