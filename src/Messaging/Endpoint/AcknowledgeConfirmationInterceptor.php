<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class AmqpAcknowledgeConfirmationInterceptor
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgeConfirmationInterceptor
{
    private function __construct(private bool $shouldStopOnError)
    {
    }

    public static function createAroundInterceptor(InterfaceToCallRegistry $interfaceToCallRegistry, PollingMetadata $pollingMetadata): AroundInterceptorReference
    {
        return AroundInterceptorReference::createWithDirectObjectAndResolveConverters($interfaceToCallRegistry, new self($pollingMetadata->isStoppedOnError()), 'ack', Precedence::MESSAGE_ACKNOWLEDGE_PRECEDENCE, '');
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $message
     * @return mixed
     * @throws Throwable
     * @throws MessagingException
     */
    public function ack(MethodInvocation $methodInvocation, Message $message, #[Reference('logger')] LoggerInterface $logger)
    {
        if (! $message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            return $methodInvocation->proceed();
        }

        $result = null;
        $exception = null;
        /** @var AcknowledgementCallback $amqpAcknowledgementCallback */
        $amqpAcknowledgementCallback = $message->getHeaders()->get($message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION));
        try {
            $result = $methodInvocation->proceed();

            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->accept();
                $logger->info(sprintf('Message with id %s acknowledged in Message Broker', $message->getHeaders()->getMessageId()));
            }
        } catch (RejectMessageException $exception) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->reject();
                $logger->info(sprintf('Message with id %s rejected in Message Broker', $message->getHeaders()->getMessageId()));
            }
        } catch (Throwable $exception) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->requeue();
                $logger->info(sprintf('Message with id %s requeued in Message Broker', $message->getHeaders()->getMessageId()));
            }
        }

        if ($this->shouldStopOnError && $exception !== null) {
            $logger->info('Should stop on error configuration enabled, stopping Message Consumer.');
            throw $exception;
        }

        return $result;
    }
}
