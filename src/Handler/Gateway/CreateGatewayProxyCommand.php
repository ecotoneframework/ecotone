<?php

namespace Messaging\Handler\Gateway;
use Messaging\Channel\DirectChannel;

/**
 * Class CreateGatewayProxyCommand
 * @package Messaging\Handler\Gateway
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class CreateGatewayProxyCommand
{
    /**
     * @var string
     */
    private $interfaceName;
    /**
     * @var string
     */
    private $methodName;
    /**
     * @var DirectChannel
     */
    private $requestChannel;
    /**
     * @var int
     */
    private $milliSecondsTimeout;
    /**
     * @var GatewayReply|null
     */
    private $gatewayReply;


}