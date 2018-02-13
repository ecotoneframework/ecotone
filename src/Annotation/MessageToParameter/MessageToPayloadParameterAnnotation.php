<?php

namespace SimplyCodedSoftware\Messaging\Annotation\MessageToParameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class PayloadParameterConverter
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 * @Target({"ANNOTATION"})
 */
class MessageToPayloadParameterAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}