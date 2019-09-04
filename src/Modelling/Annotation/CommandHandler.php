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
     * inputChannelName must be defined to connect with external channels
     *
     * @var boolean
     */
    public $ignoreMessage = false;
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     *
     * @var bool
     */
    public $dropMessageOnNotFound = false;
    /**
     * If @Aggregate was found, redirect to aggregate's method
     *
     * @var string
     */
    public $redirectToOnAlreadyExists = "";
    /**
     * Does the handler allow for same command class name / channel name handler
     *
     * @var bool
     */
    public $mustBeUnique = true;
}