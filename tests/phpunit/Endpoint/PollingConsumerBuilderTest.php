<?php
declare(strict_types=1);

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint;

use Fixture\Endpoint\ConsumerStoppingService;
use Fixture\Endpoint\ConsumerThrowingExceptionService;
use Fixture\Endpoint\MessageHandlerStoppingService;
use Fixture\Handler\ExceptionMessageHandler;
use Fixture\Handler\Processor\ThrowExceptionMessageProcessor;
use Fixture\Handler\ReplyViaHeadersMessageHandlerBuilder;
use Fixture\Transaction\FakeTransactionFactory;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingConsumer\PollingConsumerBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\PollingMetadata;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\ServiceActivator\ServiceActivatorBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class PollingConsumerBuilderTest
 * @package Test\SimplyCodedSoftware\IntegrationMessaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollingConsumerBuilderTest extends MessagingTest
{

    /**
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_creating_consumer_with_period_trigger()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeNoReturn")
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_calling_pollable_consumer_with_transactions()
    {
        $pollingConsumerBuilder = new PollingConsumerBuilder();
        $inputChannelName = "inputChannelName";
        $inputChannel = QueueChannel::create();
        $transactionFactory = FakeTransactionFactory::create();

        $directObjectReference = ConsumerStoppingService::create(null);
        $replyViaHeadersMessageHandlerBuilder = ServiceActivatorBuilder::createWithDirectReference($directObjectReference, "executeNoReturn")
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
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
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