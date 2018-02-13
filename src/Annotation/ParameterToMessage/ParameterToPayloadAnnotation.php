<?php

namespace SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class PayloadToMessageAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\ParameterToMessage
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 * @Target({"ANNOTATION"})
 */
class ParameterToPayloadAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}