<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\IntegrationMessaging\Annotation\Gateway;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class GatewayAnnotation
 * @package SimplyCodedSoftware\IntegrationMessaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class Gateway
{
    /**
     * @var string
     * @Required()
     */
    public $requestChannel;
    /**
     * @var string
     */
    public $errorChannel = "";
    /**
     * @var array
     */
    public $parameterConverters = [];
    /**
     * @var array
     */
    public $transactionFactories = [];
}