<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Annotation\Gateway;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use SimplyCodedSoftware\Messaging\Handler\Gateway\GatewayProxyBuilder;

/**
 * Class GatewayAnnotation
 * @package SimplyCodedSoftware\Messaging\Annotation
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
    /**
     * @var int
     */
    public $replyTimeoutInMilliseconds = GatewayProxyBuilder::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    /**
     * @var array
     */
    public $requiredInterceptorNames = [];
}