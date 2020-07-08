<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Annotation\InputOutputEndpointAnnotation;
use Ramsey\Uuid\Uuid;

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
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     *
     * @var bool
     */
    public $dropMessageOnNotFound = false;
    /**
     * @var array
     */
    public $identifierMetadataMapping = [];
}