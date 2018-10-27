<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;
use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Class GatewayHeaderExpression
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayHeaderExpression
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
    /**
     * @var string
     * @Required()
     */
    public $expression;
}