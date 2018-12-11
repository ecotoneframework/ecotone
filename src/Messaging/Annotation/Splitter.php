<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class TransformerAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class Splitter extends InputOutputEndpointAnnotation
{
    /**
     * @var array
     */
    public $parameterConverters = [];
}