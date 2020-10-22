<?php


namespace Ecotone\Messaging\Annotation\Parameter;

/**
 * Class AllHeaders
 * @package Ecotone\Messaging\Annotation\Parameter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Headers
{
    /**
     * @Required()
     */
    public string $parameterName;
}