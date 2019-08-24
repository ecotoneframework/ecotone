<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;

/**
 * Class CommandHandler
 * @package Ecotone\Modelling\Annotation
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
     * if endpoint is not interested in message, set to true.
     *
     * @var boolean
     */
    public $ignoreMessage = false;
    /**
     * @var bool
     */
    public $filterOutOnNotFound = false;
    /**
     * Redirect to channel when factory method found already existing aggregate
     *
     * @var string
     */
    public $redirectToOnAlreadyExists = "";
}