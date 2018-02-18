<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter;

use Fixture\Handler\Splitter\ServiceSplittingArrayPayload;
use Fixture\Handler\Splitter\WrongSplittingService;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Splitter\SplitterBuilder;
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
}