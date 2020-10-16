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
     * @Required()
     */
    public string $endpointId;
    public array $parameterConverters = [];
}