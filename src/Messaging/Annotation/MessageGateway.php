<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;
use Ecotone\Messaging\Handler\Gateway\GatewayProxyBuilder;

/**
 * Class GatewayAnnotation
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 * @Target({"METHOD"})
 */
class MessageGateway
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
     * @var int
     */
    public $replyTimeoutInMilliseconds = GatewayProxyBuilder::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    /**
     * @var array
     */
    public $requiredInterceptorNames = [];
}