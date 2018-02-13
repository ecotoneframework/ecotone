<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class RouterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
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