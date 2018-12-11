<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class ServiceActivator
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class ServiceActivator extends InputOutputEndpointAnnotation
{
    /**
     * @var bool
     */
    public $requiresReply = false;
    /**
     * @var array
     */
    public $parameterConverters = [];
}