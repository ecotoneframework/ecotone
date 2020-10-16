<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TransformerAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class Transformer extends InputOutputEndpointAnnotation
{
    public array $parameterConverters = [];
}