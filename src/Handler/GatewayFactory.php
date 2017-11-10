<?php

namespace Messaging\Handler;
use Messaging\Channel\DirectChannel;
use Messaging\Handler\Gateway\InterfaceToCall;
use Messaging\Handler\Poller\ChannelReplySender;
use Messaging\Handler\Poller\EmptyReplySender;
use Messaging\Handler\Poller\TimeoutChannelReplySender;
use Messaging\PollableChannel;
use Messaging\Support\InvalidArgumentException;

/**
 * Class GatewayFactory
 * @package Messaging\Handler
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class GatewayFactory
{
    /**
     * @param string $interfaceName
     * @param string $methodName
     * @param DirectChannel $requestChannel
     * @param int $milliSecondsTimeout
     * @param GatewayReply|null $gatewayReply
     * @return Gateway
     * @throws \Messaging\MessagingException
     */
    public function createFor(string $interfaceName, string $methodName, DirectChannel $requestChannel, int $milliSecondsTimeout, ?GatewayReply $gatewayReply)
    {
        $interfaceToCall = InterfaceToCall::create($interfaceName, $methodName);

        if ($interfaceToCall->isVoid() && $gatewayReply) {
            throw InvalidArgumentException::create("Can't create gateway with reply channel, when {$interfaceToCall} is void");
        }

        if (!$gatewayReply) {
            return new Gateway($requestChannel, new EmptyReplySender());
        }

        if ($milliSecondsTimeout > 0) {
            return new Gateway($requestChannel, new TimeoutChannelReplySender($gatewayReply, $milliSecondsTimeout));
        }

        return new Gateway($requestChannel, new ChannelReplySender($gatewayReply));
    }
}