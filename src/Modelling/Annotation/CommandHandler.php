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
    public array $parameterConverters = [];
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     */
    public bool $dropMessageOnNotFound = false;
    public array $identifierMetadataMapping = [];
}