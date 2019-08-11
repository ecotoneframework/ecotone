<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;

/**
 * Class QueryHandler
 * @package Ecotone\Modelling\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class QueryHandler extends InputOutputEndpointAnnotation
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