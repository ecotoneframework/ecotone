<?php

namespace SimplyCodedSoftware\DomainModel\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;

/**
 * Class CommandHandler
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class CommandHandler extends EndpointAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * If handler has no need in message payload, you can add name of the class name in annotation
     *
     * @var string
     */
    public $messageClassName;
}