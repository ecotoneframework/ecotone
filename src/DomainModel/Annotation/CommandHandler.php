<?php

namespace SimplyCodedSoftware\DomainModel\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;
use SimplyCodedSoftware\Messaging\Annotation\InputOutputEndpointAnnotation;

/**
 * Class CommandHandler
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class CommandHandler extends InputOutputEndpointAnnotation
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