<?php

namespace SimplyCodedSoftware\Messaging\Annotation;

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