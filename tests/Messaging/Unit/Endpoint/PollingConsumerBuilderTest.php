<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\Messaging\Unit\Endpoint;

use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\ConsumerStoppingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\ConsumerThrowingExceptionService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Endpoint\MessageHandlerStoppingService;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ExceptionMessageHandler;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\Processor\ThrowExceptionMessageProcessor;
use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\ReplyViaHeadersMessageHandlerBuilder;
use Test\SimplyCodedSoftware\Messaging\Fixture\Transaction\FakeTransactionFactory;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\Messaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\Unit\MessagingTest;

/**
 * Class PollingConsumerBuilderTest
 * @package Test\SimplyCodedSoftware\Messaging\Unit\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumerBuilderTest extends MessagingTest
{

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_creating_consumer_with_period_trigger()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeNoReturn")
                                                    ->withEndpointId("test")
                                                    ->withInputChannelName($inputChannelName);
        $pollingConsumer = $pollingConsumerBuilder->create(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => $inputChannel
            ]),
            InMemoryReferenceSearchService::createEmpty(),
            $replyViaHeadersMessageHandlerBuilder,
            PollingMetadata::create("some")
        );

        $directObjectReference->setConsumerLifecycle($pollingConsumer);
        $inputChannel->send(MessageBuilder::withPayload("somePayload")->build());
        $pollingConsumer->start();

        $this->assertEquals(
            "somePayload",
            $directObjectReference->getReceivedPayload()
        );
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public function test_calling_pollable_consumer_with_transactions()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $transactionFactory = FakeTransactionFactory::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeNoReturn")
            ->withEndpointId("test")
            ->withInputChannelName($inputChannelName);
        $pollingConsumer = $pollingConsumerBuilder->create(
            InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => $inputChannel
            ]),
            InMemoryReferenceSearchService::createWith([
                "tx" => $transactionFactory
            ]),
            $replyViaHeadersMessageHandlerBuilder,
            PollingMetadata::create("some")
                ->setTransactionFactoryReferenceNames(["tx"])
        );

        $directObjectReference->setConsumerLifecycle($pollingConsumer);
        $inputChannel->send(MessageBuilder::withPayload("somePayload")->build());

        $pollingConsumer->start();
        $this->assertTrue($transactionFactory->getCurrentTransaction()->isCommitted());
    }

    /**
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
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
        $pollingConsumer = $pollingConsumerBuilder->create(
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

        $pollingConsumer->start();

        $this->assertNotNull($errorChannel->receive());
    }
}