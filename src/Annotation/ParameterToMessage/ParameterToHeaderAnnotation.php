<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class HeaderToMessageAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\ParameterToMessage
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class ParameterToHeaderAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     * @Required()
     */
    public $headerName;
}