<?php

namespace SimplyCodedSoftware\DomainModel\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class AggregateIdentifier
 * @package SimplyCodedSoftware\DomainModel\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"PROPERTY"})
 */
class AggregateIdentifier
{
    /**
     * Name of the routing key property on messages that provides the identifier
     *
     * @var string
     */
    public $targetIdentifierName;
}