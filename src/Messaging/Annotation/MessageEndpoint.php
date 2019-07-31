<?php

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageEndpoint
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class MessageEndpoint
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}