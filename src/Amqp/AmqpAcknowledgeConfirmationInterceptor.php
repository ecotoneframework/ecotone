<?php
declare(strict_types=1);


namespace Ecotone\Amqp;

use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Endpoint\AcknowledgementCallback;
use Ecotone\Messaging\Endpoint\InterceptedConsumer;
use Ecotone\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessagingException;
use Throwable;

/**
 * Class AmqpAcknowledgeConfirmationInterceptor
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpAcknowledgeConfirmationInterceptor
{
    const PRECEDENCE = ErrorChannelInterceptor::PRECEDENCE - 1;

    public static function createAroundInterceptor() : AroundInterceptorReference
    {
        return AroundInterceptorReference::createWithDirectObject(Uuid::uuid4()->toString(), new self(), "ack", self::PRECEDENCE, "");
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
        /** @var AcknowledgementCallback $amqpAcknowledgementCallback */
        $amqpAcknowledgementCallback = $message->getHeaders()->get(AmqpHeader::HEADER_ACKNOWLEDGE);
        try {
            $result = $methodInvocation->proceed();

            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->accept();
            }
        } catch (Throwable $e) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->requeue();
            }
            throw $e;
        }

        return $result;
    }
}