<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Throwable;

/**
 * Class AmqpAcknowledgeConfirmationInterceptor
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgeConfirmationInterceptor
{
    const PRECEDENCE = ErrorChannelInterceptor::PRECEDENCE - 99;

    public static function createAroundInterceptor(string $interceptorName) : AroundInterceptorReference
    {
        return AroundInterceptorReference::createWithDirectObject($interceptorName, new self(), "ack", self::PRECEDENCE, "");
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $message
     * @return mixed
     * @throws Throwable
     * @throws MessagingException
     */
    public function ack(MethodInvocation $methodInvocation, Message $message)
    {
        if (!$message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            return $methodInvocation->proceed();
        }

        $result = null;
        /** @var AcknowledgementCallback $amqpAcknowledgementCallback */
        $amqpAcknowledgementCallback = $message->getHeaders()->get($message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION));
        try {
            $result = $methodInvocation->proceed();

            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->accept();
            }
        } catch (Throwable $e) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->requeue();
            }
        }

        return $result;
    }
}