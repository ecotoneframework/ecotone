<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class RouterAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class Router extends EndpointAnnotation
{
    /**
     * @var string
     * @Required()
     */
    public $inputChannelName;
    /**
     * @var bool
     */
    public $isResolutionRequired = true;
    /**
     * @var array
     */
    public $parameterConverters = [];
}