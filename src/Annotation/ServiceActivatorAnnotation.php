<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class ServiceActivatorAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannel = '';
    /**
     * @var string
     */
    public $outputChannel = '';
    /**
     * @var bool
     */
    public $requiresReply = false;
    /**
     * @var array
     */
    public $parameterConverters = [];
}