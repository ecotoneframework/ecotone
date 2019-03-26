<?php

namespace SimplyCodedSoftware\Messaging\Annotation\Gateway;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class GatewayHeaders
 * @package SimplyCodedSoftware\Messaging\Annotation\Gateway
 * @author  Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class GatewayHeaderArray
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
}