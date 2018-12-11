<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Gateway;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class HeaderToMessageAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class GatewayHeader
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     * @Required()
     */
    public $headerName;
    /**
     * @var string
     */
    public $expression;
    /**
     * @var mixed
     */
    public $value;
}