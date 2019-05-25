<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Amqp;

use Ramsey\Uuid\Uuid;
use SimplyCodedSoftware\Messaging\Endpoint\AcknowledgementCallback;
use SimplyCodedSoftware\Messaging\Endpoint\InterceptedConsumer;
use SimplyCodedSoftware\Messaging\Handler\Gateway\ErrorChannelInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\MessagingException;
use Throwable;

/**
 * Class AmqpAcknowledgeConfirmationInterceptor
 * @package SimplyCodedSoftware\Amqp
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