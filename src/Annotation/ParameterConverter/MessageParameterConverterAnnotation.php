<?php

namespace SimplyCodedSoftware\Messaging\Annotation\ParameterConverter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class MessageParameterConverterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\ParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MessageParameterConverterAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}