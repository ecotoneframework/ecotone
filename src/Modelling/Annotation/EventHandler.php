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
     */
    public string $listenTo = "";
    public array $parameterConverters = [];
    /**
     * If @Aggregate was not found, message can be dropped instead of throwing exception
     */
    public bool $dropMessageOnNotFound = false;
    public string $outputChannelName = '';
    /**
     * Required interceptor reference names
     */
    public array $requiredInterceptorNames = [];
    public array $identifierMetadataMapping = [];
}