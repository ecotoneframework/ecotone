<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ErrorHandler;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageChannel;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\Assert;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\MessageBuilder;

class ErrorHandler
{
    const ECOTONE_RETRY_HEADER = "ecotone_retry_number";
    const EXCEPTION_STACKTRACE = "exception-stacktrace";
    const EXCEPTION_MESSAGE = "exception-message";
    /**
     * @var RetryTemplate
     */
    private $retryTemplate;

    public function __construct(RetryTemplate $retryTemplate)
    {
        $this->retryTemplate = $retryTemplate;
    }

    public function handle(Message $errorMessage): ?Message
    {
        /** @var MessagingException $messagingException */
        $messagingException = $errorMessage->getPayload();
        $failedMessage = $messagingException->getFailedMessage();

        $retryNumber = $failedMessage->getHeaders()->containsKey(self::ECOTONE_RETRY_HEADER) ? $failedMessage->getHeaders()->get(self::ECOTONE_RETRY_HEADER) + 1 : 1;

        if (!$this->retryTemplate->canBeCalledNextTime($retryNumber)) {
            $cause = $messagingException->getCause();

            return MessageBuilder::fromMessage($failedMessage)
                    ->setHeader(self::EXCEPTION_MESSAGE, $cause ? $cause->getMessage() : $messagingException->getMessage())
                    ->setHeader(self::EXCEPTION_STACKTRACE, $cause ? $cause->getTraceAsString() : $messagingException->getTraceAsString())
                    ->build();
        }

        /** @var MessageChannel $messageChannel */
        $messageChannel = $failedMessage->getHeaders()->get(MessageHeaders::POLLED_CHANNEL);
        $messageChannel->send(
            MessageBuilder::fromMessage($failedMessage)
                ->setHeader(MessageHeaders::DELIVERY_DELAY, $this->retryTemplate->calculateNextDelay($retryNumber))
                ->setHeader(self::ECOTONE_RETRY_HEADER, $retryNumber)
                ->build()
        );

        return null;
    }
}