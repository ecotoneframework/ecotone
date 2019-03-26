<?php

namespace SimplyCodedSoftware\DomainModel\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Annotation\EndpointAnnotation;

/**
 * Class EventHandler
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class EventHandler extends EndpointAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}