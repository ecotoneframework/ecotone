<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Recoverability;

use Ecotone\Messaging\Attribute\Parameter\Reference;
use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Handler\Logger\LoggingGateway;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * licence Apache-2.0
 */
class ErrorHandler
{
    public const ECOTONE_RETRY_HEADER = 'ecotone_retry_number';
    public const EXCEPTION_STACKTRACE = 'exception-stacktrace';
    public const EXCEPTION_FILE = 'exception-file';
    public const EXCEPTION_LINE = 'exception-line';
    public const EXCEPTION_CODE = 'exception-code';
    public const EXCEPTION_MESSAGE = 'exception-message';

    public function __construct(
        private RetryTemplate $delayedRetryTemplate,
        private bool $hasDeadLetterOutput,
        private LoggingGateway $loggingGateway,
    ) {
    }

    public function handle(
        Message $errorMessage,
        ChannelResolver $channelResolver,
        #[Reference] LoggingGateway $logger
    ): ?Message {
        $failedMessage = $errorMessage;
        $cause = $errorMessage->getHeaders()->get(ErrorContext::EXCEPTION);
        $retryNumber = $failedMessage->getHeaders()->containsKey(self::ECOTONE_RETRY_HEADER) ? $failedMessage->getHeaders()->get(self::ECOTONE_RETRY_HEADER) + 1 : 1;

        if (! $failedMessage->getHeaders()->containsKey(MessageHeaders::POLLED_CHANNEL_NAME)) {
            $this->loggingGateway->error(
                'Failed to handle Error Message via your Retry Configuration, as it does not contain information about origination channel from which it was polled.
                    This means that most likely Synchronous Dead Letter is configured with Retry Configuration which works only for Asynchronous configuration.',
                $failedMessage,
                ['exception' => $cause],
            );

            throw $cause;
        }
        /** @var MessageChannel $messageChannel */
        $messageChannel = $channelResolver->resolve($failedMessage->getHeaders()->get(MessageHeaders::POLLED_CHANNEL_NAME));

        $messageBuilder = MessageBuilder::fromMessage($failedMessage);
        if ($messageBuilder->containsKey(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION)) {
            $messageBuilder->removeHeader($messageBuilder->getHeaderWithName(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION));
        }

        $messageBuilder->removeHeaders([
            MessageHeaders::DELIVERY_DELAY,
            MessageHeaders::TIME_TO_LIVE,
            MessageHeaders::CONSUMER_ACK_HEADER_LOCATION,
            ErrorContext::EXCEPTION,
            self::ECOTONE_RETRY_HEADER,
        ]);

        if ($this->shouldBeSendToDeadLetter($retryNumber)) {
            if (! $this->hasDeadLetterOutput) {
                $logger->error(
                    sprintf(
                        'Discarding message %s as no dead letter channel was defined. Retried maximum number of `%s` times. Due to: %s',
                        $failedMessage->getHeaders()->getMessageId(),
                        $retryNumber,
                        $cause->getMessage()
                    ),
                    $failedMessage,
                    ['exception' => $cause],
                );

                return null;
            }

            $logger->error(
                sprintf(
                    'Sending message `%s` to dead letter channel, as retried maximum number of `%s` times. Due to: %s',
                    $failedMessage->getHeaders()->getMessageId(),
                    $retryNumber,
                    $cause->getMessage()
                ),
                $failedMessage,
                ['exception' => $cause],
            );

            return $messageBuilder->build();
        }

        $delayMs = $this->delayedRetryTemplate->calculateNextDelay($retryNumber);
        $logger->info(
            sprintf(
                'Retrying message with id `%s` with delay of `%d` ms. %s. Due to %s',
                $failedMessage->getHeaders()->getMessageId(),
                $delayMs,
                $this->delayedRetryTemplate->getMaxAttempts()
                    ? sprintf('Try %d out of %s', $retryNumber, $this->delayedRetryTemplate->getMaxAttempts())
                    : '',
                $cause->getMessage()
            ),
            $failedMessage,
            ['exception' => $cause],
        );
        $messageChannel->send(
            $messageBuilder
                ->setHeader(MessageHeaders::DELIVERY_DELAY, $delayMs)
                ->removeHeaders(ErrorContext::WHOLE_ERROR_CONTEXT)
                ->setHeader(self::ECOTONE_RETRY_HEADER, $retryNumber)
                ->build()
        );

        return null;
    }

    private function shouldBeSendToDeadLetter(int $retryNumber): bool
    {
        return ! $this->delayedRetryTemplate->canBeCalledNextTime($retryNumber);
    }
}
