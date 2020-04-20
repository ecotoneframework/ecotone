<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class MessageConsumer
{
    /**
     * @var string
     * @Required()
     */
    public $endpointId;
    /**
     * @var array
     */
    public $parameterConverters = [];
}