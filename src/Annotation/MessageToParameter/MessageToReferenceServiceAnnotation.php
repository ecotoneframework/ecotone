<?php

namespace SimplyCodedSoftware\Messaging\Annotation\MessageToParameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ReferenceServiceParameterConverterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\MessageToParameter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"ANNOTATION"})
 */
class MessageToReferenceServiceAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     */
    public $referenceName = '';
}