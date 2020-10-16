<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Splitter;

use Test\Ecotone\Messaging\Fixture\Handler\Splitter\ServiceSplittingArrayPayload;
use Test\Ecotone\Messaging\Fixture\Handler\Splitter\WrongSplittingService;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Config\InMemoryChannelResolver;
use Ecotone\Messaging\Handler\InMemoryReferenceSearchService;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class SplitterBuilderTest
 * @package Ecotone\Messaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SplitterBuilderTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_splitting_incoming_message_where_service_returns_payloads()
    {
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($referenceName, "splitToPayload");

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

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_splitter_does_not_return_array()
    {
        $referenceName = "ref-a";
        $splitter = SplitterBuilder::create($referenceName, "splittingWithReturnString");

        $service = new WrongSplittingService();

        $this->expectException(InvalidArgumentException::class);

        $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createWith([
                $referenceName => $service
            ])
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_creating_splitter_with_direct_reference()
    {
        $splitter = SplitterBuilder::createWithDirectObject(new ServiceSplittingArrayPayload(), "splitToPayload");

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

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws \Exception
     */
    public function test_splitting_directly_from_message_without_service()
    {
        $splitter = SplitterBuilder::createMessagePayloadSplitter();

        $splitter = $splitter->build(
            InMemoryChannelResolver::createEmpty(),
            InMemoryReferenceSearchService::createEmpty()
        );

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertNotNull($outputChannel->receive());
        $this->assertNotNull($outputChannel->receive());
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = "someName";

        $this->assertEquals(
            SplitterBuilder::create("ref-name", "method-name")
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf("Splitter - %s:%s with name `%s` for input channel `%s`", "ref-name", "method-name", $endpointName, $inputChannelName)
        );
    }
}