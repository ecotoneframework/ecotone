<?php
declare(strict_types=1);


namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;
use Ecotone\Messaging\Conversion\ArrayToJson\ArrayToJsonConverter;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;
use Ecotone\Messaging\Handler\Logger\LoggingService;
use Ecotone\Messaging\Handler\TypeDescriptor;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class LoggingServiceTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingServiceTest extends TestCase
{
    public function test_calling_logger_with_correct_debug_level()
    {
        $logger = $this
                    ->getMockBuilder(LoggerInterface::class)
                    ->getMock();

        $payload = "someData";
        $message = MessageBuilder::withPayload($payload)->build();

        $logger
            ->expects($this->once())
            ->method("debug")
            ->with($payload);

        $loggingService = new LoggingService(AutoCollectionConversionService::createEmpty(), $logger);
        $loggingService->log(LoggingLevel::createDebug(), $message);
    }

    public function test_throwing_exception_if_wrong_log_level_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        LoggingLevel::create("bla", false);
    }

    public function test_serializing_payload_if_is_not_primitive()
    {
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $payload = ["some"];
        $message = MessageBuilder::withPayload($payload)->build();

        $logger
            ->expects($this->once())
            ->method("debug")
            ->with(serialize($payload));

        $loggingService = new LoggingService(AutoCollectionConversionService::createWith([
            new SerializingConverter()
        ]), $logger);
        $loggingService->log(LoggingLevel::createDebug(), $message);
    }

    public function test_calling_to_string_method_if_possible()
    {
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $payload = Uuid::uuid4();
        $message = MessageBuilder::withPayload($payload)->build();

        $logger
            ->expects($this->once())
            ->method("debug")
            ->with($payload->toString());

        $loggingService = new LoggingService(AutoCollectionConversionService::createEmpty(), $logger);
        $loggingService->log(LoggingLevel::createDebug(), $message);
    }

    public function test_serializing_payload_to_json_if_converter_available()
    {
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $payload = ["some"];
        $message = MessageBuilder::withPayload($payload)
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(TypeDescriptor::ARRAY))
                    ->build();

        $logger
            ->expects($this->once())
            ->method("debug")
            ->with(\json_encode($payload));

        $loggingService = new LoggingService(AutoCollectionConversionService::createWith([
            new ArrayToJsonConverter()
        ]), $logger);
        $loggingService->log(LoggingLevel::createDebug(), $message);
    }

    public function test_logging_full_message()
    {
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $payload = "some";
        $message = MessageBuilder::withPayload($payload)->build();

        $logger
            ->expects($this->once())
            ->method("debug")
            ->with($payload, [
                "headers" => \json_encode([
                    "id" => $message->getHeaders()->get(MessageHeaders::MESSAGE_ID),
                    "timestamp" => $message->getHeaders()->get(MessageHeaders::TIMESTAMP)
                ])
            ]);

        $loggingService = new LoggingService(AutoCollectionConversionService::createEmpty(), $logger);
        $loggingService->log(LoggingLevel::createDebugWithFullMessage(), $message);
    }
}