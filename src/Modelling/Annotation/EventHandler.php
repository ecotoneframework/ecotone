<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\EndpointAnnotation;

/**
 * Class EventHandler
 * @package Ecotone\Modelling\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class EventHandler extends EndpointAnnotation
{
    /**
     * Registers event handler to listen from defined inputs
     * e.g. from single - "ecotone.modelling.created"
     * e.g. from multiple - "ecotone.modelling.*"
     *
     * @var string
     */
    public $listenTo = "";
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * if endpoint is not interested in message, set to true.
     * ListenTo must be defined to connect endpoint with external channels
     *
     * @var string
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
     * @var string
     */
    public $outputChannelName = '';
    /**
     * Required interceptor reference names
     *
     * @var array
     */
    public $requiredInterceptorNames = [];
}