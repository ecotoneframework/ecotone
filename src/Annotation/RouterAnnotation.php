<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class RouterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class RouterAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannel;
    /**
     * @var bool
     */
    public $isResolutionRequired = true;
    /**
     * @var array
     */
    public $parameterConverters = [];
}