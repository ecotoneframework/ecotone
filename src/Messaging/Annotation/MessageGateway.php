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
     * @Required()
     */
    public string $requestChannel;
    public string $errorChannel = "";
    public array $parameterConverters = [];
    public int $replyTimeoutInMilliseconds = GatewayProxyBuilder::DEFAULT_REPLY_MILLISECONDS_TIMEOUT;
    public array $requiredInterceptorNames = [];
    public ?string $replyContentType = null;
}