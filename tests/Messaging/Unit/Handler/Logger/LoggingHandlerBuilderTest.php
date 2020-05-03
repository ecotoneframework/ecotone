<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Psr\Log\LoggerInterface;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Conversion\AutoCollectionConversionService;
use Ecotone\Messaging\Conversion\ConversionService;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Logger\LoggingHandlerBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\InterceptorConverterBuilder;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter\MessageConverterBuilder;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger\ServiceActivatorWithLoggerExample;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class LoggingHandlerBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggingHandlerBuilderTest extends MessagingTest
{
    public function test_logger_passing_messaging_through()
    {
        $logger = LoggerExample::create();
        $queueChannel = QueueChannel::create();
        $loggingHandler = LoggingHandlerBuilder::createForAfter()
                            ->withOutputMessageChannel("outputChannel")
                            ->withMethodParameterConverters([
                                MessageConverterBuilder::create("message"),
                                InterceptorConverterBuilder::create("log", InterfaceToCall::create(ServiceActivatorWithLoggerExample::class, "sendMessage"), [])
                            ])
                            ->build(
                                InMemoryChannelResolver::createFromAssociativeArray([
                                    "outputChannel" => $queueChannel
                                ]),
                                InMemoryReferenceSearchService::createWith([
                                    ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createWith([]),
                                    LoggingHandlerBuilder::LOGGER_REFERENCE => $logger
                                ])
                            );

        $message = MessageBuilder::withPayload("some")->build();
        $loggingHandler->handle($message);

        $this->assertMessages(
            $message,
            $queueChannel->receive()
        );
    }

    public function test_given_payload_is_string_when_logging_without_debug_level_then_default_debug_level_should_be_used()
    {
        $logger = $this
            ->getMockBuilder(LoggerInterface::class)
            ->getMock();

        $queueChannel = QueueChannel::create();
        $loggingHandler = LoggingHandlerBuilder::createForBefore()
            ->withOutputMessageChannel("outputChannel")
            ->withMethodParameterConverters([
                MessageConverterBuilder::create("message"),
                InterceptorConverterBuilder::create("log", InterfaceToCall::create(ServiceActivatorWithLoggerExample::class, "sendMessage"), [])
            ])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    "outputChannel" => $queueChannel
                ]),
                InMemoryReferenceSearchService::createWith([
                    ConversionService::REFERENCE_NAME => AutoCollectionConversionService::createEmpty(),
                    LoggingHandlerBuilder::LOGGER_REFERENCE => $logger
                ])
            );

        $message = MessageBuilder::withPayload("some")->build();

        $logger
            ->expects($this->once())
            ->method("info")
            ->with("some", [
                "headers" => \json_encode([
                    "id" => $message->getHeaders()->getMessageId(),
                    "timestamp" => $message->getHeaders()->getTimestamp()
                ])
            ]);

        $loggingHandler->handle($message);
    }
}