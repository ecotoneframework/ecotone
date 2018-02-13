<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class PayloadToMessageAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage
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