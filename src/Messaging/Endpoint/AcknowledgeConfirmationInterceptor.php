<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Ecotone\Messaging\Endpoint\PollingConsumer\RejectMessageException;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\MethodInvocation;
use Ecotone\Messaging\Handler\Recoverability\RetryRunner;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Exception;
use Throwable;

/**
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class AcknowledgeConfirmationInterceptor
{
    public function __construct(private RetryRunner $retryRunner, private LoggingGateway $logger)
    {
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
        $messageChannelName = $message->getHeaders()->containsKey(MessageHeaders::POLLED_CHANNEL_NAME) ? $message->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME) : 'unknown';

        $this->logger->info(
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

        $this->handle($message, $methodInvocation, $messageChannelName, $message->getHeaders()->get(MessageHeaders::CONSUMER_POLLING_METADATA));
    }

    private function handle(Message $message, MethodInvocation $methodInvocation, string $messageChannelName, PollingMetadata $pollingMetadata): void
    {
        $logger = $this->logger;
        $retryStrategy = RetryTemplateBuilder::exponentialBackoffWithMaxDelay(10, 10, 1000)
            ->maxRetryAttempts(3)
            ->build();

        /** @var AcknowledgementCallback $acknowledgementCallback */
        $acknowledgementCallback = $message->getHeaders()->get($message->getHeaders()->get(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION));
        try {
            $methodInvocation->proceed();
        } catch (RejectMessageException) {
            $this->retryRunner->runWithRetry(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback) {
                $acknowledgementCallback->reject();

                $logger->info(
                    sprintf('Message with id `%s` rejected in Message Channel `%s`', $message->getHeaders()->getMessageId(), $messageChannelName),
                    $message
                );
            }, $retryStrategy, $message, Exception::class, sprintf('Rejecting Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));

            return;
        } catch (Throwable $exception) {
            if ($acknowledgementCallback->getFailureStrategy() === FinalFailureStrategy::STOP || $pollingMetadata->isStoppedOnError()) {
                $acknowledgementCallback->release();

                $logger->critical(
                    sprintf(
                        'Acknowledgment mode is set to stop on failure. Stopping Message Consumer without acknowledgment to avoid Message loss.  Error: %s',
                        $exception->getMessage()
                    ),
                    $message,
                    ['exception' => $exception, 'channel' => $messageChannelName],
                );

                throw $exception;
            }

            $this->retryRunner->runWithRetry(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback, $exception) {

                if ($acknowledgementCallback->getFailureStrategy() === FinalFailureStrategy::IGNORE) {
                    $acknowledgementCallback->reject();
                    $logger->info(
                        sprintf('Message with id `%s` rejected in Message Channel `%s`', $message->getHeaders()->getMessageId(), $messageChannelName),
                        $message
                    );

                    return;
                }

                if ($acknowledgementCallback->getFailureStrategy() === FinalFailureStrategy::RELEASE) {
                    $acknowledgementCallback->release();
                    $logger->info(
                        sprintf(
                            'Message with id `%s` released to Message Channel `%s`. Due to %s',
                            $message->getHeaders()->getMessageId(),
                            $messageChannelName,
                            $exception->getMessage()
                        ),
                        $message,
                        ['exception' => $exception, 'channel' => $messageChannelName]
                    );

                    return;
                }

                $acknowledgementCallback->resend();
                $logger->info(
                    sprintf(
                        'Message with id `%s` resent to Message Channel `%s`. Due to %s',
                        $message->getHeaders()->getMessageId(),
                        $messageChannelName,
                        $exception->getMessage()
                    ),
                    $message,
                    ['exception' => $exception, 'channel' => $messageChannelName]
                );
            }, $retryStrategy, $message, Exception::class, sprintf('Re-sending Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));

            return;
        }

        // Only auto-acknowledge if auto-ack is enabled
        if ($acknowledgementCallback->isAutoAcked()) {
            $this->retryRunner->runWithRetry(function () use ($message, $logger, $messageChannelName, $acknowledgementCallback) {
                $acknowledgementCallback->accept();

                $logger->info(
                    sprintf('Message with id `%s` was acknowledged in Message Channel `%s`', $message->getHeaders()->getMessageId(), $messageChannelName),
                    $message
                );
            }, $retryStrategy, $message, Exception::class, sprintf('Acknowledging Message in Message Channel `%s` failed. Trying to self-heal and retry.', $messageChannelName));
        }
    }
}
