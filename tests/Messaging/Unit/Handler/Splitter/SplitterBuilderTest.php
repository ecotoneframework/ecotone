<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Splitter;

use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use Test\Ecotone\Messaging\Fixture\Handler\Splitter\ServiceSplittingArrayPayload;
use Test\Ecotone\Messaging\Fixture\Handler\Splitter\WrongSplittingService;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class SplitterBuilderTest
 * @package Ecotone\Messaging\Handler\Splitter
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
 */
class SplitterBuilderTest extends MessagingTest
{
    public function test_splitting_incoming_message_where_service_returns_payloads()
    {
        $splitter = SplitterBuilder::createWithDefinition(ServiceSplittingArrayPayload::class, 'splitToPayload');

        $splitter = ComponentTestBuilder::create()->build($splitter);

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertEquals(1, $outputChannel->receive()->getPayload());
        $this->assertEquals(2, $outputChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_splitter_does_not_return_array()
    {
        $splitter = SplitterBuilder::createWithDefinition(WrongSplittingService::class, 'splittingWithReturnString');

        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()->build($splitter);
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws Exception
     */
    public function test_creating_splitter_with_direct_reference()
    {
        $splitter = SplitterBuilder::createWithDefinition(ServiceSplittingArrayPayload::class, 'splitToPayload');

        $splitter = ComponentTestBuilder::create()->build($splitter);

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertEquals(1, $outputChannel->receive()->getPayload());
        $this->assertEquals(2, $outputChannel->receive()->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws Exception
     */
    public function test_splitting_directly_from_message_without_service()
    {
        $splitter = SplitterBuilder::createMessagePayloadSplitter();

        $splitter = ComponentTestBuilder::create()->build($splitter);

        $outputChannel = QueueChannel::create();
        $splitter->handle(MessageBuilder::withPayload([1, 2])->setReplyChannel($outputChannel)->build());

        $this->assertNotNull($outputChannel->receive());
        $this->assertNotNull($outputChannel->receive());
    }

    public function test_splitting_will_set_new_content_type()
    {
        $splitter = SplitterBuilder::createMessagePayloadSplitter();

        $splitter = ComponentTestBuilder::create()->build($splitter);

        $outputChannel = QueueChannel::create();
        $splitter->handle(
            MessageBuilder::withPayload([1, 2])
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter('array<int>'))
                ->setReplyChannel($outputChannel)
                ->build()
        );

        $this->assertEquals(MediaType::createApplicationXPHPWithTypeParameter('int')->toString(), $outputChannel->receive()->getHeaders()->getContentType()->toString());
        $this->assertEquals(MediaType::createApplicationXPHPWithTypeParameter('int')->toString(), $outputChannel->receive()->getHeaders()->getContentType()->toString());
    }

    public function test_converting_to_string()
    {
        $this->assertIsString(
            (string)SplitterBuilder::createMessagePayloadSplitter()
                ->withInputChannelName('inputChannel')
                ->withEndpointId('someName')
        );
    }
}
