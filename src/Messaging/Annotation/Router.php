<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class RouterAnnotation
 * @package Ecotone\Messaging\Annotation
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
    public bool $isResolutionRequired = true;
    public array $parameterConverters = [];
}