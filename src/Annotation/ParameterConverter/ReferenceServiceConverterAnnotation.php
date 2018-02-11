<?php

namespace SimplyCodedSoftware\Messaging\Annotation\ParameterConverter;

use Doctrine\Common\Annotations\Annotation\Required;
use SimplyCodedSoftware\Messaging\Annotation\ParameterConverterAnnotation;

/**
 * Class ReferenceServiceParameterConverterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\ParameterConverter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class ReferenceServiceConverterAnnotation implements ParameterConverterAnnotation
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