<?php

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MediaTypeConverter
 * @package Ecotone\Messaging\Annotation
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"CLASS"})
 */
class MediaTypeConverter
{
    /**
     * If not configured it will take class name as reference
     *
     * @var string
     */
    public $referenceName;
}