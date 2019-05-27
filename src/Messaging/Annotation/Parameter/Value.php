<?php

namespace SimplyCodedSoftware\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageToStaticValueParameterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\MessageToParameter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
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