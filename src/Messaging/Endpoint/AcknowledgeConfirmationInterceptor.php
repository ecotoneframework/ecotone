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
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Precedence;
use Exception;
use Throwable;

/**
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
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
        $messageChannelName = $message->getHeaders()->containsKey(MessageHeaders::POLLED_CHANNEL_NAME) ? $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME) : 'unknown';

        $logger->info(
            sprintf(
                'Message with id `%s` received from Message Channel `%s`',
                $message->getHeaders()->getMessageId(),
                $messageChannelName
            ),
            $message
        );
        if (! $message->getHeaders()->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            return $methodInvocation->proceed();
        }

        $this->handle($message, $methodInvocation, $logger, $messageChannelName, $message->getHeaders()->get(MessageHeaders::CONSUMER_POLLING_METADATA));
    }

    public function getDefinition(): Definition
    {
        return new Definition(self::class);
    }

    private function handle(Message $message, MethodInvocation $methodInvocation, LoggingGateway $logger, string $messageChannelName, PollingMetadata $pollingMetadata): void
    {
        $retryStrategy = RetryTemplateBuilder::exponentialBackoffWithMaxDelay(10, 10, 1000)
            ->maxRetryAttempts(3)
            ->build();

        /** @var AcknowledgementCallback $acknowledgementCallback */
        $acknowledgementCallback = $message->getHeaders()->get($message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION));
        try {
            $methodInvocation->proceed();
        } catch (RejectMessageException) {
            if ($acknowledgementCallback->isAutoAck()) {
                $retryStrategy->runCallbackWithRetries(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback) {
                    $acknowledgementCallback->reject();

                    $logger->info(
                        sprintf('Message with id `%s` rejected in Message Channel `%s`', $message->getHeaders()->getMessageId(), $messageChannelName),
                        $message
                    );
                }, $message, Exception::class, $logger, sprintf('Rejecting Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));
            }

            return;
        } catch (Throwable $exception) {
            if (! $acknowledgementCallback->isAutoAck()) {
                $logger->critical(
                    sprintf(
                        'Acknowledgment mode is not auto and unrecoverable error happened. Stopping Message Consumer without acknowledgment to avoid Message loss.  Error: %s',
                        $exception->getMessage()
                    ),
                    $message,
                    ['exception' => $exception, 'channel' => $messageChannelName],
                );

                throw $exception;
            }

            $retryStrategy->runCallbackWithRetries(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback, $exception) {
                $acknowledgementCallback->requeue();

                $logger->info(
                    sprintf(
                        'Message with id `%s` requeued in Message Channel `%s`. Due to %s',
                        $message->getHeaders()->getMessageId(),
                        $messageChannelName,
                        $exception->getMessage()
                    ),
                    $message,
                    ['exception' => $exception, 'channel' => $messageChannelName]
                );
            }, $message, Exception::class, $logger, sprintf('Re-queuing Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));

            if ($pollingMetadata->isStoppedOnError() === true) {
                $logger->info(
                    'Should stop on error configuration enabled, stopping Message Consumer.',
                    $message,
                    ['channel' => $messageChannelName]
                );

                throw $exception;
            }

            return;
        }

        if ($acknowledgementCallback->isAutoAck()) {
            $retryStrategy->runCallbackWithRetries(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback) {
                $acknowledgementCallback->accept();

                $logger->info(
                    sprintf('Message with id `%s` was acknowledged in Message Channel `%s`', $message->getHeaders()->getMessageId(), $messageChannelName),
                    $message
                );
            }, $message, Exception::class, $logger, sprintf('Acknowledging Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));
        }
    }
}
