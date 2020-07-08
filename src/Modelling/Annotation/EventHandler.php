<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\EndpointAnnotation;
use Ecotone\Messaging\Annotation\IdentifiedAnnotation;
use Ecotone\Messaging\Config\ConfigurationException;
use Ramsey\Uuid\Uuid;

/**
 * Class EventHandler
 * @package Ecotone\Modelling\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class EventHandler extends IdentifiedAnnotation
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
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     *
     * @var bool
     */
    public $dropMessageOnNotFound = false;
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
    /**
     * @var array
     */
    public $identifierMetadataMapping = [];
}