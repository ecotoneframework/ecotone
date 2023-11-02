<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Handler\InterfaceToCallRegistry;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Throwable;

/**
 * Class AmqpAcknowledgeConfirmationInterceptor
 * @package Ecotone\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AcknowledgeConfirmationInterceptor implements DefinedObject
{
    public static function createAroundInterceptorBuilder(InterfaceToCallRegistry $interfaceToCallRegistry): AroundInterceptorBuilder
    {
        return AroundInterceptorBuilder::createWithDirectObjectAndResolveConverters($interfaceToCallRegistry, new self(), 'ack', Precedence::MESSAGE_ACKNOWLEDGE_PRECEDENCE, '');
    }

    /**
     * @param MethodInvocation $methodInvocation
     * @param Message $message
     * @return mixed
     * @throws Throwable
     * @throws MessagingException
     */
    public function ack(MethodInvocation $methodInvocation, Message $message, #[Reference] LoggingGateway $logger)
    {
        $logger->info(
            sprintf(
                'Message with id `%s` received from Message Channel `%s`',
                $message->getHeaders()->getMessageId(),
                $message->getHeaders()->containsKey(MessageHeaders::POLLED_CHANNEL_NAME) ? $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME) : 'unknown'
            ),
            $message
        );
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
                $logger->info(
                    sprintf('Message with id `%s` acknowledged in Message Channel', $message->getHeaders()->getMessageId()),
                    $message
                );
            }
        } catch (RejectMessageException $exception) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->reject();
                $logger->info(
                    sprintf('Message with id `%s` rejected in Message Channel', $message->getHeaders()->getMessageId()),
                    $message
                );
            }
        } catch (Throwable $exception) {
            if ($amqpAcknowledgementCallback->isAutoAck()) {
                $amqpAcknowledgementCallback->requeue();
                $logger->info(
                    sprintf(
                        'Message with id `%s` requeued in Message Channel. Due to %s',
                        $message->getHeaders()->getMessageId(),
                        $exception->getMessage()
                    ),
                    $message
                );
            }
        }

        $pollingMetadata = $message->getHeaders()->get(MessageHeaders::CONSUMER_POLLING_METADATA);
        if ($pollingMetadata->isStoppedOnError() === true && $exception !== null) {
            $logger->info(
                'Should stop on error configuration enabled, stopping Message Consumer.',
                $message
            );
            throw $exception;
        }

        return $result;
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }
}
