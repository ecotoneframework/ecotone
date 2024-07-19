<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Filter;

use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\Container\InterfaceToCallReference;
use Ecotone\Messaging\Handler\Filter\MessageFilterBuilder;
use Ecotone\Messaging\MessageHeaderDoesNotExistsException;
use Ecotone\Messaging\MessagingException;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use Test\Ecotone\Messaging\Fixture\Handler\Selector\MessageSelectorExample;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class MessageFilterBuilderTest
 * @package Ecotone\Messaging\Handler\Filter
 * @author  Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class MessageFilterBuilderTest extends MessagingTest
{
    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws Exception
     */
    public function test_forwarding_message_if_selector_returns_false()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference(MessageSelectorExample::class, MessageSelectorExample::create())
            ->withMessageHandler(
                MessageFilterBuilder::createWithReferenceName(MessageSelectorExample::class, InterfaceToCallReference::create(MessageSelectorExample::class, 'accept'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNotNull(
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws MessagingException
     * @throws Exception
     */
    public function test_discard_message_if_selector_returns_true()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference(MessageSelectorExample::class, MessageSelectorExample::create())
            ->withMessageHandler(
                MessageFilterBuilder::createWithReferenceName(MessageSelectorExample::class, InterfaceToCallReference::create(MessageSelectorExample::class, 'refuse'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    public function test_forwarding_message_by_bool_header_filter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageFilterBuilder::createBoolHeaderFilter('filterOut')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNotNull(
            $messaging->sendDirectToChannel($inputChannel, metadata: [
                'filterOut' => false,
            ])
        );
    }

    public function test_discarding_message_by_bool_header_filter()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageFilterBuilder::createBoolHeaderFilter('filterOut')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel, metadata: [
                'filterOut' => true,
            ])
        );
    }

    public function test_throwing_exception_when_bool_header_filter_and_no_header_presented()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageFilterBuilder::createBoolHeaderFilter('filterOut')
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->expectException(MessageHeaderDoesNotExistsException::class);

        $messaging->sendDirectToChannel($inputChannel);
    }

    public function test_forwarding_message_by_bool_header_filter_when_no_header_presented()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageFilterBuilder::createBoolHeaderFilter('filterOut', defaultResultWhenHeaderIsMissing: false)
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNotNull(
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    public function test_discarding_message_by_bool_header_filter_when_no_header_presented()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                MessageFilterBuilder::createBoolHeaderFilter('filterOut', defaultResultWhenHeaderIsMissing: true)
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel)
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_throwing_exception_if_selector_return_type_is_different_than_boolean()
    {
        $this->expectException(InvalidArgumentException::class);

        ComponentTestBuilder::create()
            ->withReference(MessageSelectorExample::class, MessageSelectorExample::create())
            ->withMessageHandler(
                MessageFilterBuilder::createWithReferenceName(MessageSelectorExample::class, InterfaceToCallReference::create(MessageSelectorExample::class, 'wrongReturnType'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
            )
            ->build();
    }

    /**
     * @throws MessagingException
     */
    public function test_publishing_message_to_discard_channel_if_defined()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($discardChannelName = 'discardChannel'))
            ->withReference(MessageSelectorExample::class, MessageSelectorExample::create())
            ->withMessageHandler(
                MessageFilterBuilder::createWithReferenceName(MessageSelectorExample::class, InterfaceToCallReference::create(MessageSelectorExample::class, 'refuse'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withDiscardChannelName($discardChannelName)
            )
            ->build();

        $this->assertNull(
            $messaging->sendDirectToChannel($inputChannel)
        );
        $this->assertNotNull(
            $messaging->receiveMessageFrom($discardChannelName)
        );
    }

    /**
     * @throws MessagingException
     */
    public function test_throwing_exception_on_discard_if_defined()
    {
        $messaging = ComponentTestBuilder::create()
            ->withReference(MessageSelectorExample::class, MessageSelectorExample::create())
            ->withMessageHandler(
                MessageFilterBuilder::createWithReferenceName(MessageSelectorExample::class, InterfaceToCallReference::create(MessageSelectorExample::class, 'refuse'))
                    ->withInputChannelName($inputChannel = 'inputChannel')
                    ->withThrowingExceptionOnDiscard(true)
            )
            ->build();

        $this->expectException(MessagingException::class);

        $messaging->sendDirectToChannel($inputChannel);
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = 'someName';

        $this->assertIsString(
            (string)MessageFilterBuilder::createWithReferenceName('ref-name', InterfaceToCallReference::create(MessageSelectorExample::class, 'refuse'))
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
        );
    }
}
