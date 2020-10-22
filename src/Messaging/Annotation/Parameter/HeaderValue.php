<?php


namespace Ecotone\Messaging\Annotation\Parameter;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class HeaderValue
 * @package Ecotone\Messaging\Annotation\Parameter
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class HeaderValue
{
    /**
     * @Required()
     */
    public string $headerName;
    /**
     * @Required()
     */
    public string $headerValue;
}