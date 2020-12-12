<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Recoverability;

use Ecotone\Messaging\Handler\ChannelResolver;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\MessageBuilder;

class ErrorHandler
{
    const ECOTONE_RETRY_HEADER = "ecotone_retry_number";
    const EXCEPTION_STACKTRACE = "exception-stacktrace";
    const EXCEPTION_FILE = "exception-file";
    const EXCEPTION_LINE = "exception-line";
    const EXCEPTION_CODE = "exception-code";
    const EXCEPTION_MESSAGE = "exception-message";

    private RetryTemplate $delayedRetryTemplate;
    private bool $hasDeadLetterOutput;

    public function __construct(RetryTemplate $delayedRetryTemplate, bool $hasDeadLetterOutput)
    {
        $this->delayedRetryTemplate = $delayedRetryTemplate;
        $this->hasDeadLetterOutput = $hasDeadLetterOutput;
    }

    public function handle(ErrorMessage $errorMessage, ChannelResolver $channelResolver): ?Message
    {
        /** @var MessagingException $messagingException */
        $messagingException = $errorMessage->getPayload();
        $failedMessage = $messagingException->getFailedMessage();
        $cause = $messagingException->getCause() ? $messagingException->getCause() : $messagingException;
        $retryNumber = $failedMessage->getHeaders()->containsKey(self::ECOTONE_RETRY_HEADER) ? $failedMessage->getHeaders()->get(self::ECOTONE_RETRY_HEADER) + 1 : 1;

        if (!$failedMessage->getHeaders()->containsKey(MessageHeaders::POLLED_CHANNEL_NAME)) {
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
            MessageHeaders::CONSUMER_ACK_HEADER_LOCATION
        ]);

        if ($this->shouldBeSendToDeadLetter($retryNumber)) {
            if (!$this->hasDeadLetterOutput) {
                return null;
            }

            $messageBuilder->removeHeader(self::ECOTONE_RETRY_HEADER);

            return $messageBuilder
                    ->setHeader(ErrorContext::EXCEPTION_MESSAGE, $cause->getMessage())
                    ->setHeader(ErrorContext::EXCEPTION_STACKTRACE, $cause->getTraceAsString())
                    ->setHeader(ErrorContext::EXCEPTION_FILE, $cause->getFile())
                    ->setHeader(ErrorContext::EXCEPTION_LINE, $cause->getLine())
                    ->setHeader(ErrorContext::EXCEPTION_CODE, $cause->getCode())
                    ->build();
        }

        $messageChannel->send(
            $messageBuilder
                ->setHeader(MessageHeaders::DELIVERY_DELAY, $this->delayedRetryTemplate->calculateNextDelay($retryNumber))
                ->setHeader(self::ECOTONE_RETRY_HEADER, $retryNumber)
                ->build()
        );

        return null;
    }

    private function shouldBeSendToDeadLetter(int $retryNumber): bool
    {
        return !$this->delayedRetryTemplate->canBeCalledNextTime($retryNumber);
    }
}