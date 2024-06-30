<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Splitter;

use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
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
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('outputChannel'))
            ->withMessageHandler(
                SplitterBuilder::createWithDefinition(ServiceSplittingArrayPayload::class, 'splitToPayload')
                    ->withInputChannelName('inputChannel')
                    ->withOutputMessageChannel('outputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', [1, 2]);

        $this->assertEquals(1, $messaging->receiveMessageFrom('outputChannel')->getPayload());
        $this->assertEquals(2, $messaging->receiveMessageFrom('outputChannel')->getPayload());
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     */
    public function test_throwing_exception_if_splitter_does_not_return_array()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withMessageHandler(
                SplitterBuilder::createWithDefinition(WrongSplittingService::class, 'splittingWithReturnString')
            )
            ->build();
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws Exception
     */
    public function test_splitting_directly_from_message_without_service()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('outputChannel'))
            ->withMessageHandler(
                SplitterBuilder::createMessagePayloadSplitter()
                    ->withInputChannelName('inputChannel')
                    ->withOutputMessageChannel('outputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', [1, 2]);

        $this->assertEquals(1, $messaging->receiveMessageFrom('outputChannel')->getPayload());
        $this->assertEquals(2, $messaging->receiveMessageFrom('outputChannel')->getPayload());
    }

    public function test_splitting_will_set_new_content_type()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('outputChannel'))
            ->withMessageHandler(
                SplitterBuilder::createMessagePayloadSplitter()
                    ->withInputChannelName('inputChannel')
                    ->withOutputMessageChannel('outputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', [1, 2]);

        $this->assertEquals(MediaType::createApplicationXPHPWithTypeParameter('int')->toString(), $messaging->receiveMessageFrom('outputChannel')->getHeaders()->getContentType()->toString());
        $this->assertEquals(MediaType::createApplicationXPHPWithTypeParameter('int')->toString(), $messaging->receiveMessageFrom('outputChannel')->getHeaders()->getContentType()->toString());
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
