<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Ecotone\Messaging\Conversion\ArrayToJson\ArrayToJsonConverter;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Conversion\ObjectToSerialized\SerializingConverter;
use Ecotone\Messaging\Handler\Logger\Annotation\LogAfter;
use Ecotone\Messaging\Handler\Logger\LoggingInterceptor;
use Ecotone\Messaging\Handler\Logger\LoggingLevel;
use Ecotone\Messaging\Handler\Logger\StubLoggingGateway;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

use function json_encode;

use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use Ramsey\Uuid\Uuid;

/**
 * Class LoggingServiceTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Logger
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class LoggingInterceptorTest extends TestCase
{
    public function test_throwing_exception_if_wrong_log_level_passed()
    {
        $this->expectException(InvalidArgumentException::class);

        LoggingLevel::create('bla', false);
    }

    public function test_serializing_payload_if_is_not_primitive()
    {
        $loggingService = new StubLoggingGateway();
        $loggingInterceptor = new LoggingInterceptor($loggingService, AutoCollectionConversionService::createWith([
            new SerializingConverter(),
        ]));

        $payload = ['some'];
        $message = MessageBuilder::withPayload($payload)->build();

        $loggingInterceptor->log($message, new LogAfter(LogLevel::DEBUG, false));

        $logs = $loggingService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(LogLevel::DEBUG, $logs[0]->level);
        $this->assertEquals(addslashes(serialize($payload)), $logs[0]->message);
    }

    public function test_calling_to_string_method_if_possible()
    {
        $loggingService = new StubLoggingGateway();
        $loggingInterceptor = new LoggingInterceptor($loggingService, AutoCollectionConversionService::createWith([
            new SerializingConverter(),
        ]));

        $payload = Uuid::uuid4();
        $message = MessageBuilder::withPayload($payload)->build();

        $loggingInterceptor->log($message, new LogAfter(LogLevel::DEBUG, false));
        $logs = $loggingService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(LogLevel::DEBUG, $logs[0]->level);
        $this->assertEquals($payload->toString(), $logs[0]->message);
    }

    public function test_serializing_payload_to_json_if_converter_available()
    {
        $loggingService = new StubLoggingGateway();
        $loggingInterceptor = new LoggingInterceptor($loggingService, AutoCollectionConversionService::createWith([
            new ArrayToJsonConverter(),
        ]));

        $payload = ['some'];
        $message = MessageBuilder::withPayload($payload)
                    ->setContentType(MediaType::createApplicationXPHPWithTypeParameter(Type::ARRAY))
                    ->build();

        $loggingInterceptor->log($message, new LogAfter(LogLevel::DEBUG, false));
        $logs = $loggingService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(LogLevel::DEBUG, $logs[0]->level);
        $this->assertEquals(json_encode($payload), $logs[0]->message);
    }

    public function test_logging_full_message()
    {
        $loggingService = new StubLoggingGateway();
        $loggingInterceptor = new LoggingInterceptor($loggingService, AutoCollectionConversionService::createWith([
            new ArrayToJsonConverter(),
        ]));

        $payload = 'some';
        $message = MessageBuilder::withPayload($payload)->build();

        $loggingInterceptor->log($message, new LogAfter(LogLevel::DEBUG, true));
        $logs = $loggingService->getLogs();
        $this->assertCount(1, $logs);
        $this->assertEquals(LogLevel::DEBUG, $logs[0]->level);
        $this->assertEquals($payload, $logs[0]->message);
        $this->assertEquals([
            'headers' => json_encode([
                'id' => $message->getHeaders()->get(MessageHeaders::MESSAGE_ID),
                'timestamp' => $message->getHeaders()->get(MessageHeaders::TIMESTAMP),
                'correlationId' => $message->getHeaders()->getCorrelationId(),
            ]),
        ], $logs[0]->context);
    }
}
