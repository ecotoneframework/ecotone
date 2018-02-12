<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class TransformerAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class TransformerAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;
    /**
     * @var string
     */
    public $outputChannelName = '';
    /**
     * @var array
     */
    public $parameterConverters = [];
}