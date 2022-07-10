<?php


namespace Test\Ecotone\Dbal;


use Ecotone\Dbal\DbalInboundChannelAdapterBuilder;
use Ecotone\Dbal\DbalOutboundChannelAdapterBuilder;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Support\MessageBuilder;
use Enqueue\Dbal\DbalConnectionFactory;
use Ramsey\Uuid\Uuid;

class ChannelAdapterTest extends DbalMessagingTest
{
    public function test_sending_and_receiving_message()
    {
        $queueName = Uuid::uuid4()->toString();
        $requestChannelName = Uuid::uuid4()->toString();
        $requestChannel = QueueChannel::create();
        $timeoutInMilliseconds = 1;

        $inboundChannelAdapter = DbalInboundChannelAdapterBuilder::createWith(
            Uuid::uuid4()->toString(),
            $queueName,
            $requestChannelName
        )
            ->withReceiveTimeout($timeoutInMilliseconds)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $requestChannelName => $requestChannel
                ]),
                InMemoryReferenceSearchService::createWith([DbalConnectionFactory::class => $this->getConnectionFactory()]),
                PollingMetadata::create(Uuid::uuid4()->toString())
                    ->setExecutionTimeLimitInMilliseconds($timeoutInMilliseconds)
            );

        $outbountChannelAdapter = DbalOutboundChannelAdapterBuilder::create($queueName)
                                    ->build(
                                        InMemoryChannelResolver::createEmpty(),
                                        InMemoryReferenceSearchService::createWith([DbalConnectionFactory::class => $this->getConnectionFactory()])
                                    );

        $payload = "some";
        $outbountChannelAdapter->handle(MessageBuilder::withPayload($payload)->build());

        $receivedMessage = $this->receiveMessage($inboundChannelAdapter, $requestChannel);
        $this->assertNotNull($receivedMessage, "Not received message");
        $this->assertEquals($payload, $receivedMessage->getPayload());

        $this->assertNull($this->receiveMessage($inboundChannelAdapter, $requestChannel), "Received message twice instead of one");
    }

    /**
     * @param \Ecotone\Messaging\Endpoint\ConsumerLifecycle $inboundChannelAdapter
     * @param QueueChannel $requestChannel
     * @return \Ecotone\Messaging\Message|null
     */
    private function receiveMessage(\Ecotone\Messaging\Endpoint\ConsumerLifecycle $inboundChannelAdapter, QueueChannel $requestChannel)
    {
        $inboundChannelAdapter->run();
        $receivedMessage = $requestChannel->receive();
        return $receivedMessage;
    }
}