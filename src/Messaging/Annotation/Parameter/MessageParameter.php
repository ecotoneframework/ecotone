<?php

namespace Ecotone\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class MessageParameterConverterAnnotation
 * @package Ecotone\Messaging\Annotation\MessageToParameter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class MessageParameter
{
    /**
     * @Required()
     */
    public string $parameterName;
}