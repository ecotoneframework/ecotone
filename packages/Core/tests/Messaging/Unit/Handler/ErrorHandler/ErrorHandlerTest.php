<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\ErrorHandler;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandler;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\ErrorMessage;
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
        $errorHandler = new ErrorHandler($retryTemplate, false);

        $this->assertNull(
            $errorHandler->handle($this->createFailedMessage(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                    ->build()
            ), InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel]))
        );

        $this->assertNull($errorHandler->handle(
            $this->createFailedMessage(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                    ->build()
            ),
            InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel])
        ));
        $this->assertNotNull($consumedChannel->receive());
    }

    private function createFailedMessage(Message $message, \Throwable $exception = null): ErrorMessage
    {
        return ErrorMessage::create(MessageHandlingException::fromOtherException($exception ?? new MessageHandlingException(), $message));
    }

    public function test_calculating_correct_delay_for_retry_template()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate, false);

        $errorHandler->handle(
            $this->createFailedMessage(
                MessageBuilder::withPayload("some")
                    ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                    ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                    ->build()
            ),
                InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel])
        );

        $this->assertEquals(40, $consumedChannel->receive()->getHeaders()->get(MessageHeaders::DELIVERY_DELAY));
    }

    public function test_if_exceeded_retries_returning_message_with_exception_in_headers()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->maxRetryAttempts(1)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate, true);

        $resultMessage = $errorHandler->handle($this->createFailedMessage(
            MessageBuilder::withPayload("payload")
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                ->build(),
            new \InvalidArgumentException("exceptionMessage")
        ), InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel]));

        $this->assertEquals("exceptionMessage", $resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_MESSAGE));
        $this->assertNotEmpty($resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_STACKTRACE));
    }

    public function test_if_exceeded_retries_and_no_dead_letter_defined_drop_message()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->maxRetryAttempts(1)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate, false);

        $resultMessage = $errorHandler->handle($this->createFailedMessage(
            MessageBuilder::withPayload("payload")
                ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                ->build(),
            new \InvalidArgumentException("exceptionMessage")
        ), InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel]));

        $this->assertNull($resultMessage);
    }

    public function test_if_exceeded_retries_returning_message_with_causation_exception_if_exists()
    {
        $retryTemplate = RetryTemplateBuilder::exponentialBackoff(10, 2)
            ->maxRetryAttempts(1)
            ->build();

        $consumedChannel = QueueChannel::create();
        $errorHandler = new ErrorHandler($retryTemplate, true);

        $resultMessage = $errorHandler->handle(
            $this->createFailedMessage(
                MessageBuilder::withPayload("payload")
                    ->setHeader(MessageHeaders::POLLED_CHANNEL_NAME, "errorChannel")
                    ->setHeader(ErrorHandler::ECOTONE_RETRY_HEADER, 2)
                    ->build(),
                new InvalidArgumentException("causation")),
            InMemoryChannelResolver::createFromAssociativeArray(["errorChannel" => $consumedChannel])
        );

        $this->assertEquals("causation", $resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_MESSAGE));
        $this->assertNotEmpty($resultMessage->getHeaders()->get(ErrorHandler::EXCEPTION_STACKTRACE));
    }

    public function test_rethrowing_exception_if_no_polled_channel_exists()
    {
        $errorHandler = new ErrorHandler(
            RetryTemplateBuilder::exponentialBackoff(1, 2)
                ->maxRetryAttempts(2)
                ->build(),
            false
        );

        $this->expectException(InvalidArgumentException::class);

        $errorHandler->handle($this->createFailedMessage(
            MessageBuilder::withPayload("some")
                ->build(),
            new \InvalidArgumentException()
        ), InMemoryChannelResolver::createEmpty());
    }
}