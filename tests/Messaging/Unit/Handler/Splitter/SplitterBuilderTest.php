<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Splitter;

use Ecotone\Api\ExtensionObject\MediaType;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Handler\Splitter\SplitterBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Test\Ecotone\Messaging\Unit\MessagingTestCase;

/**
 * SplitterBuilder::createMessagePayloadSplitter() and __toString() have no
 * public #[Splitter] attribute surface -- SplitterAttributeTest covers the
 * user-reachable service-backed splitting behaviour via #[Splitter].
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class SplitterBuilderTest extends MessagingTestCase
{
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
