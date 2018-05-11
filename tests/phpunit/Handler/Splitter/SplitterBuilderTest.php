<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter;

use Fixture\Handler\Splitter\ServiceSplittingArrayPayload;
use Fixture\Handler\Splitter\WrongSplittingService;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\MessagingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\InvalidArgumentException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class SplitterBuilderTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilderTest extends MessagingTest
{
    public function test_splitting_incoming_message_where_service_returns_payloads()
    {
        $inputChannelName = "inputChannel";
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($inputChannelName, $referenceName, "splitToPayload");

        $service = new ServiceSplittingArrayPayload();
        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $service
            ])
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(2, $payload);
        $this->assertEquals(1, $outputChannel->receive()->getPayload());
    }

    public function test_throwing_exception_if_splitter_does_not_return_array()
    {
        $inputChannelName = "inputChannel";
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($inputChannelName, $referenceName, "splittingWithReturnString");

        $service = new WrongSplittingService();

        $this->expectException(InvalidArgumentException::class);

        $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $service
            ])
        );
    }

    public function test_creating_splitter_with_direct_reference()
    {
        $inputChannelName = "inputChannel";
        $splitter = SplitterBuilder::createWithDirectObject($inputChannelName, new ServiceSplittingArrayPayload(), "splitToPayload");

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $payload = $outputChannel->receive()->getPayload();
        $this->assertEquals(2, $payload);
        $this->assertEquals(1, $outputChannel->receive()->getPayload());
    }

    public function test_splitting_directly_from_message_without_service()
    {
        $inputChannelName = "inputChannel";
        $splitter = SplitterBuilder::createMessagePayloadSplitter($inputChannelName);

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertNotNull($outputChannel->receive());
        $this->assertNotNull($outputChannel->receive());
    }

    public function test_throwing_exception_if_message_for_payload_splitter_do_not_contains_array()
    {
        $inputChannelName = "inputChannel";
        $splitter = SplitterBuilder::createMessagePayloadSplitter($inputChannelName);

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $this->expectException(MessagingException::class);

        $splitter->handle(MessageBuilder::withPayload("test")->setReplyChannel(QueueChannel::create())->build());
    }
}