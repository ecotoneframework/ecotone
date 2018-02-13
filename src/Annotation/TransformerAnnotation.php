<?php

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TransformerAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
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