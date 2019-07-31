<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\Poller;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Messaging\Transaction\Null\NullTransaction;
use Ecotone\Messaging\Transaction\Null\NullTransactionFactory;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerContinuouslyWorkingService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\Ecotone\Messaging\Fixture\Endpoint\ConsumerThrowingExceptionService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class PollingConsumerBuilderTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumerBuilderTest extends MessagingTest
{

    /**
     * @throws MessagingException
     */
    public function test_creating_consumer_with_default_period_trigger()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeNoReturn")
            ->withEndpointId("test")
            ->withInputChannelName($inputChannelName);
        $pollingConsumer = $pollingConsumerBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => $inputChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $replyViaHeadersMessageHandlerBuilder,
            PollingMetadata::create("some")
        );

        $directObjectReference->setConsumerLifecycle($pollingConsumer);
        $inputChannel->send(MessageBuilder::withPayload("somePayload")->build());
        $pollingConsumer->run();

        $this->assertEquals(
            "somePayload",
            $directObjectReference->getReceivedPayload()
        );
    }

    /**
     * @throws MessagingException
     */
    public function __test_calling_with_message_consumption_limit()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $pollableChannelName = "pollableChannelName";
        $pollableChannel = QueueChannel::create();

        $directObjectReference = ConsumerContinuouslyWorkingService::create();
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeReturn")
            ->withEndpointId("test")
            ->withInputChannelName($pollableChannelName);

        $pollingConsumer = $pollingConsumerBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                $pollableChannelName => $pollableChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $replyViaHeadersMessageHandlerBuilder,
            PollingMetadata::create("some")
                ->setHandledMessageLimit(3)
        );

        $pollableChannel->send(MessageBuilder::withPayload("somePayload")->build());
        $pollableChannel->send(MessageBuilder::withPayload("somePayload")->build());
        $pollableChannel->send(MessageBuilder::withPayload("somePayload")->build());

        $pollingConsumer->run();
        $this->assertNull($pollableChannel->receive());
    }

    /**
     * @throws MessagingException
     */
    public function test_passing_message_to_error_channel_on_failure()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $errorChannelName = "errorChannel";
        $inputChannel = QueueChannel::create();
        $errorChannel = QueueChannel::create();

        $directObjectReference = ConsumerThrowingExceptionService::create();
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "execute")
            ->withEndpointId("test")
            ->withInputChannelName($inputChannelName);

        $pollingConsumer = $pollingConsumerBuilder->build(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => $inputChannel,
                $errorChannelName => $errorChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $replyViaHeadersMessageHandlerBuilder,
            PollingMetadata::create("some")
                ->setErrorChannelName($errorChannelName)
        );

        $directObjectReference->setConsumerLifecycle($pollingConsumer);
        $inputChannel->send(MessageBuilder::withPayload("somePayload")->build());

        $pollingConsumer->run();

        $this->assertNotNull($errorChannel->receive());
    }
}