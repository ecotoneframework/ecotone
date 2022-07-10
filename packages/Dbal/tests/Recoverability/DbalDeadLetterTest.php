<?php

namespace Test\Ecotone\Dbal\Recoverability;

use Doctrine\DBAL\Connection;
use Ecotone\Dbal\Recoverability\DbalDeadLetter;
use Ecotone\Messaging\Conversion\InMemoryConversionService;
use Ecotone\Messaging\Handler\MessageHandlingException;
use Ecotone\Messaging\Handler\Recoverability\ErrorContext;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageConverter\DefaultHeaderMapper;
use Ecotone\Messaging\Support\ErrorMessage;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Dbal\DbalMessagingTest;

class DbalDeadLetterTest extends DbalMessagingTest
{
    protected function setUp(): void
    {
        /** @var Connection $connection */
        $connection = $this->getConnectionFactory()->createContext()->getDbalConnection();

        $connection->executeStatement(sprintf(<<<SQL
    DROP TABLE IF EXISTS %s
SQL, DbalDeadLetter::DEFAULT_DEAD_LETTER_TABLE));

        $connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        /** @var Connection $dbalConnection */
        $dbalConnection = $this->getConnectionFactory()->createContext()->getDbalConnection();

        try {
            $dbalConnection->rollBack();
        }catch (\Exception) {}
    }

    public function __test_retrieving_error_message_details()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $errorMessage = MessageBuilder::withPayload("")->build();
        $dbalDeadLetter->store($errorMessage);

        $this->assertEquals(
            $errorMessage,
            $dbalDeadLetter->show($errorMessage->getHeaders()->getMessageId())
        );
    }

    public function __test_storing_wrapped_error_message()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));


        $errorMessage = MessageBuilder::withPayload("")->build();
        $dbalDeadLetter->store($this->createFailedMessage($errorMessage));

        $this->assertEquals(
            $errorMessage->getHeaders()->getMessageId(),
            $dbalDeadLetter->show($errorMessage->getHeaders()->getMessageId())->getHeaders()->getMessageId()
        );
    }

    private function createFailedMessage(Message $message, \Throwable $exception = null): Message
    {
        return ErrorMessage::create(MessageHandlingException::fromOtherException($exception ?? new MessageHandlingException(), $message));
    }

    public function test_listing_error_messages()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $errorMessage = MessageBuilder::withPayload("error1")
                                ->setMultipleHeaders([
                                    ErrorContext::EXCEPTION_STACKTRACE => "#12",
                                    ErrorContext::EXCEPTION_LINE => 120,
                                    ErrorContext::EXCEPTION_FILE => "dbalDeadLetter.php",
                                    ErrorContext::EXCEPTION_CODE => 1,
                                    ErrorContext::EXCEPTION_MESSAGE => "some",
                                ])
                                ->build();
        $dbalDeadLetter->store($errorMessage);

        $this->assertEquals(
            [ErrorContext::fromHeaders($errorMessage->getHeaders()->headers())],
            $dbalDeadLetter->list(1, 0)
        );
    }

    public function __test_deleting_error_message()
    {
        $dbalDeadLetter = new DbalDeadLetter($this->getConnectionFactory(), DefaultHeaderMapper::createAllHeadersMapping(InMemoryConversionService::createWithoutConversion()));

        $message = MessageBuilder::withPayload("error2")->build();
        $dbalDeadLetter->store($message);
        $dbalDeadLetter->delete($message->getHeaders()->getMessageId());

        $this->assertEquals(
            [],
            $dbalDeadLetter->list(1, 0)
        );
    }
}