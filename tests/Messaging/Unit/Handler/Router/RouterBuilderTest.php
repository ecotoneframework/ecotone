<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Router;

use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Handler\DestinationResolutionException;
use Ecotone\Messaging\Handler\InterfaceToCall;
use Ecotone\Messaging\Handler\Router\RouterBuilder;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Test\ComponentTestBuilder;
use Exception;
use stdClass;
use Test\Ecotone\Messaging\Fixture\Router\MultipleChannelRouter;
use Test\Ecotone\Messaging\Fixture\Router\Order;
use Test\Ecotone\Messaging\Fixture\Router\SingleChannelRouter;
use Test\Ecotone\Messaging\Unit\MessagingTest;

/**
 * Class RouterBuilderTest
 * @package Ecotone\Messaging\Handler\Router
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 *
 * @internal
 */
/**
 * licence Apache-2.0
 * @internal
 */
class RouterBuilderTest extends MessagingTest
{
    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_message_to_single_channel()
    {
        $chanelName = 'buyChannel';
        $objectToInvokeReference = 'service-a';

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($chanelName))
            ->withReference($objectToInvokeReference, SingleChannelRouter::createWithChosenChannelName($chanelName))
            ->withMessageHandler(
                RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(SingleChannelRouter::class, 'pick'))
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', 'some');

        $this->assertNotNull($messaging->receiveMessageFrom($chanelName));
        ;
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_message_to_multiple_channels()
    {
        $objectToInvokeReference = 'service-a';
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel2'))
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([
                'channel1',
                'channel2',
            ]))
            ->withMessageHandler(
                RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick'))
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', 'some');

        $this->assertNotNull($messaging->receiveMessageFrom('channel1'));
        $this->assertNotNull($messaging->receiveMessageFrom('channel2'));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_resolution_is_required()
    {
        $chanelName = 'buyChannel';
        $objectToInvokeReference = 'service-a';

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($chanelName))
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([]))
            ->withMessageHandler(
                RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick'))
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->expectException(DestinationResolutionException::class);

        $messaging->sendDirectToChannel('inputChannel', 'some');
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_if_no_resolution_required_not_throwing_exception_when_no_resolution()
    {
        $chanelName = 'buyChannel';
        $objectToInvokeReference = 'service-a';

        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($chanelName))
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([]))
            ->withMessageHandler(
                RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick'))
                    ->withInputChannelName('inputChannel')
                    ->setResolutionRequired(false)
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', 'some');

        $this->assertNull($messaging->receiveMessageFrom($chanelName));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_based_on_payload_type()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel2'))
            ->withMessageHandler(
                RouterBuilder::createPayloadTypeRouter([
                    stdClass::class => 'channel1',
                    Order::class => 'channel2',
                ])
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', new stdClass());

        $this->assertNotNull($messaging->receiveMessageFrom('channel1'));
        $this->assertNull($messaging->receiveMessageFrom('channel2'));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_to_default_when_not_hit()
    {
        $defaultResolutionChannel = 'default';
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('default'))
            ->withMessageHandler(
                RouterBuilder::createPayloadTypeRouter([Order::class => 'channel2'])
                    ->withDefaultResolutionChannel($defaultResolutionChannel)
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', new stdClass());

        $this->assertNull($messaging->receiveMessageFrom('channel1'));
        $this->assertNotNull($messaging->receiveMessageFrom($defaultResolutionChannel));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_by_payload_type_without_mapping()
    {
        $channelName = stdClass::class;
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($channelName))
            ->withMessageHandler(
                RouterBuilder::createPayloadTypeRouterByClassName()
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', new stdClass());

        $this->assertNotNull($messaging->receiveMessageFrom($channelName));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_with_header_value()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($privateChannelName = 'channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($publicChannelName  = 'channel2'))
            ->withMessageHandler(
                RouterBuilder::createHeaderMappingRouter($headerName = 'type', [
                    'private' => $privateChannelName,
                    'public' => $publicChannelName,
                ])
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendMessageDirectToChannel(
            'inputChannel',
            MessageBuilder::withPayload('some')
                ->setHeader($headerName, 'private')
                ->build()
        );

        $this->assertNotNull($messaging->receiveMessageFrom($privateChannelName));
        $this->assertNull($messaging->receiveMessageFrom($publicChannelName));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_payload_is_not_object()
    {
        $messaging = ComponentTestBuilder::create()
            ->withMessageHandler(
                RouterBuilder::createPayloadTypeRouterByClassName()
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $this->expectException(InvalidArgumentException::class);

        $messaging->sendDirectToChannel('inputChannel', 'some');
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws Exception
     */
    public function test_recipient_list_router()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($privateChannelName = 'channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel($publicChannelName  = 'channel2'))
            ->withMessageHandler(
                RouterBuilder::createRecipientListRouter(['channel1', 'channel2'])
                    ->withInputChannelName('inputChannel')
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', 'some');

        $this->assertNotNull($messaging->receiveMessageFrom($privateChannelName));
        $this->assertNotNull($messaging->receiveMessageFrom($publicChannelName));
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_applying_sequence_to_recipient_list()
    {
        $messaging = ComponentTestBuilder::create()
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel1'))
            ->withChannel(SimpleMessageChannelBuilder::createQueueChannel('channel2'))
            ->withMessageHandler(
                RouterBuilder::createRecipientListRouter(['channel1', 'channel2'])
                    ->withInputChannelName('inputChannel')
                    ->withApplySequence(true)
            )
            ->build();

        $messaging->sendDirectToChannel('inputChannel', [1, 2]);

        $firstMessage = $messaging->receiveMessageFrom('channel1');
        $this->assertEquals(1, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
        $this->assertEquals(2, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
        $secondMessage = $messaging->receiveMessageFrom('channel2');
        $this->assertEquals(2, $secondMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
        $this->assertEquals(2, $secondMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
    }

    public function test_converting_to_string()
    {
        $inputChannelName = 'inputChannel';
        $endpointName = 'someName';

        $this->assertEquals(
            RouterBuilder::create('ref-name', InterfaceToCall::create(MultipleChannelRouter::class, 'pick'))
                ->withInputChannelName($inputChannelName)
                ->withEndpointId($endpointName),
            sprintf('Router for input channel `%s` with name `%s`', $inputChannelName, $endpointName)
        );
    }
}
