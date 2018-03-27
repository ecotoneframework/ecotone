<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\MessageToParameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class HeaderParameterConverter
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 * @Target({"ANNOTATION"})
 */
class MessageToHeaderParameterAnnotation
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
    /**
     * @var bool
     */
    public $isRequired = true;
}