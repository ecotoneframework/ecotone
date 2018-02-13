<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageParameterConverterAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class MessageParameterAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}