<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class PayloadToMessageAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation()
 */
class GatewayPayload
{
    /**
     * @var string
     * @Required()
     */
    public $parameterName;
    /**
     * @var string
     */
    public $expression;
}