<?php

namespace Ecotone\Modelling\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TargetAggregateIdentifier
 * @package Ecotone\Modelling\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"PROPERTY"})
 */
class TargetAggregateIdentifier
{
    /**
     * @var string
     */
    public $identifierName = null;
}