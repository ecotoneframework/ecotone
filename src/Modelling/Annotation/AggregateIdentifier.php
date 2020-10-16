<?php

namespace Ecotone\Modelling\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregateIdentifier
 * @package Ecotone\Modelling\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"PROPERTY"})
 */
class AggregateIdentifier
{
    /**
     * Name of the routing key property on messages that provides the identifier
     */
    public string $targetIdentifierName;
}