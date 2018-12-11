<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Gateway;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class StaticHeaderToMessageAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class GatewayHeaderValue
{
    /**
     * @var string
     * @Required()
     */
    public $headerName;
    /**
     * @var string
     * @Required()
     */
    public $headerValue;
}