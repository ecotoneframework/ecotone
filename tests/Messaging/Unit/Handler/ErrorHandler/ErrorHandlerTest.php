<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Handler\ErrorHandler\ErrorHandler;
use Ecotone\Messaging\Handler\ErrorHandler\RetryTemplateBuilder;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ErrorHandlerTest extends TestCase
{
    public function test_retrying_message_according_to_retry_template()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(1, 2)
            ->maxRetryAttempts(2)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate);

        $this->assertNull(
            $errorHandler->handle($this->createFailedMessage(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageHeaders::POLLED_CHANNEL, $consumedChannel)
                    ->build()
            ))
        );

        $this->assertNull($errorHandler->handle($this->createFailedMessage($consumedChannel->receive())));
        $this->assertNotNull($consumedChannel->receive());
    }

    private function createFailedMessage(Message $message): Message
    {
        return MessageBuilder::withPayload(
            MessageHandlingException::createWithFailedMessage(
                "exceptionMessage",
                $message
            )
        )->build();
    }

    public function test_calculating_correct_delay_for_retry_template()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate);

        $errorHandler->handle($this->createFailedMessage(
            MessageBuilder::withPayload("some")
                ->setHeader(MessageHeaders::POLLED_CHANNEL, $consumedChannel)
                ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                ->build()
        ));

        $this->assertEquals(40, $consumedChannel->receive()->getHeaders()->get(MessageHeaders::DELIVERY_DELAY));
    }

    public function test_if_exceeded_retries_returning_message_with_exception_in_headers()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->maxRetryAttempts(1)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate);

        $resultMessage = $errorHandler->handle($this->createFailedMessage(
            MessageBuilder::withPayload("payload")
                ->setHeader(MessageHeaders::POLLED_CHANNEL, $consumedChannel)
                ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                ->build()
        ));

        $this->assertEquals("exceptionMessage", $resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_MESSAGE));
        $this->assertNotEmpty($resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_STACKTRACE));
    }

    public function test_if_exceeded_retries_returning_message_with_causation_exception_if_exists()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->maxRetryAttempts(1)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate);

        $resultMessage = $errorHandler->handle(
            MessageBuilder::withPayload(
                MessageHandlingException::fromOtherException(
                    new InvalidArgumentException("causation"),
                    MessageBuilder::withPayload("payload")
                        ->setHeader(MessageHeaders::POLLED_CHANNEL, $consumedChannel)
                        ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                        ->build()
                ))
                ->build()
        );

        $this->assertEquals("causation", $resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_MESSAGE));
        $this->assertNotEmpty($resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_STACKTRACE));
    }
}