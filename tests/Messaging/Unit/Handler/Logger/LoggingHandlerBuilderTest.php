<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Handler\Logger;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Conversion\AutoCollectionConversionService;
use SimplyCodedSoftware\Messaging\Conversion\ConversionService;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\InterfaceToCall;
use SimplyCodedSoftware\Messaging\Handler\Logger\LoggingHandlerBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\InterceptorConverterBuilder;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\MessageConverterBuilder;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Annotation\MessageEndpoint\ServiceActivator\WithLogger\ServiceActivatorWithLoggerExample;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class LoggingHandlerBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Handler\Logger
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
                                InterceptorConverterBuilder::create(InterfaceToCall::create(ServiceActivatorWithLoggerExample::class, "sendMessage"), [])
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
                InterceptorConverterBuilder::create(InterfaceToCall::create(ServiceActivatorWithLoggerExample::class, "sendMessage"), [])
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