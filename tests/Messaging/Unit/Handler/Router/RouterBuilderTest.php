<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler\Router;

use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\QueueChannel;
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
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
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
        $targetChannel = QueueChannel::create();
        $objectToInvokeReference = 'service-a';

        $router = ComponentTestBuilder::create()
            ->withChannel($chanelName, $targetChannel)
            ->withReference($objectToInvokeReference, SingleChannelRouter::createWithChosenChannelName($chanelName))
            ->build(RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(SingleChannelRouter::class, 'pick')));

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_message_to_multiple_channels()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();

        $objectToInvokeReference = 'service-a';
        $router = ComponentTestBuilder::create()
            ->withChannel('channel1', $targetChannel1)
            ->withChannel('channel2', $targetChannel2)
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([
                'channel1',
                'channel2',
            ]))
            ->build(RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick')));

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
        $this->assertMessages($message, $targetChannel2->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_resolution_is_required()
    {
        $objectToInvokeReference = 'service-a';
        $router = ComponentTestBuilder::create()
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([]))
            ->build(RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick')));

        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->expectException(DestinationResolutionException::class);

        $router->handle($message);
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_if_no_resolution_required_not_throwing_exception_when_no_resolution()
    {
        $objectToInvokeReference = 'service-a';
        $router = ComponentTestBuilder::create()
            ->withReference($objectToInvokeReference, MultipleChannelRouter::createWithChosenChannelName([]))
            ->build(RouterBuilder::create($objectToInvokeReference, InterfaceToCall::create(MultipleChannelRouter::class, 'pick'))
                ->setResolutionRequired(false));

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_with_payload_type()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = 'input';

        $router = ComponentTestBuilder::create()
            ->withChannel('channel1', $targetChannel1)
            ->withChannel('channel2', $targetChannel2)
            ->withChannel($inputChannelName, DirectChannel::create())
            ->build(RouterBuilder::createPayloadTypeRouter([
                stdClass::class => 'channel1',
                Order::class => 'channel2',
            ])
                ->withInputChannelName($inputChannelName));

        $message = MessageBuilder::withPayload(new stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_to_default_when_not_hit()
    {
        $targetChannel = QueueChannel::create();

        $defaultResolutionChannel = 'default';
        $router = ComponentTestBuilder::create()
            ->withChannel($defaultResolutionChannel, $targetChannel)
            ->build(
                RouterBuilder::createPayloadTypeRouter([Order::class => 'channel2'])
                    ->withDefaultResolutionChannel($defaultResolutionChannel)
            );

        $message = MessageBuilder::withPayload(new stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_by_payload_type_without_mapping()
    {
        $targetChannel = QueueChannel::create();
        $inputChannelName = 'input';

        $router = ComponentTestBuilder::create()
            ->withChannel($inputChannelName, DirectChannel::create())
            ->withChannel(stdClass::class, $targetChannel)
            ->build(RouterBuilder::createPayloadTypeRouterByClassName()
                ->withInputChannelName($inputChannelName));

        $message = MessageBuilder::withPayload(new stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_routing_with_header_value()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $headerName = 'type';

        $router = ComponentTestBuilder::create()
            ->withChannel('channel1', $targetChannel1)
            ->withChannel('channel2', $targetChannel2)
            ->build(RouterBuilder::createHeaderMappingRouter($headerName, [
                'private' => 'channel1',
                'public' => 'channel2',
            ]));

        $message = MessageBuilder::withPayload('some')
                    ->setHeader($headerName, 'private')
                    ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_throwing_exception_if_payload_is_not_object()
    {
        $router = ComponentTestBuilder::create()
            ->build(RouterBuilder::createPayloadTypeRouterByClassName());

        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->expectException(InvalidArgumentException::class);

        $router->handle($message);
    }

    /**
     * @throws \Ecotone\Messaging\MessagingException
     * @throws Exception
     */
    public function test_recipient_list_router()
    {
        $channel1 = QueueChannel::create();
        $channel2 = QueueChannel::create();

        $router = ComponentTestBuilder::create()
            ->withChannel('channel1', $channel1)
            ->withChannel('channel2', $channel2)
            ->build(RouterBuilder::createRecipientListRouter(['channel1', 'channel2']));

        $message = MessageBuilder::withPayload('some')->build();

        $router->handle($message);

        $this->assertEquals($message, $channel1->receive());
        $this->assertEquals($message, $channel2->receive());
    }

    /**
     * @throws Exception
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function test_applying_sequence_to_recipient_list()
    {
        $channel1 = QueueChannel::create();
        $channel2 = QueueChannel::create();

        $router = ComponentTestBuilder::create()
            ->withChannel('channel1', $channel1)
            ->withChannel('channel2', $channel2)
            ->build(RouterBuilder::createRecipientListRouter(['channel1', 'channel2'])
                ->withApplySequence(true));

        $message = MessageBuilder::withPayload('some')->build();

        $router->handle($message);

        $firstMessage = $channel1->receive();
        $this->assertEquals(1, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_NUMBER));
        $this->assertEquals(2, $firstMessage->getHeaders()->get(MessageHeaders::SEQUENCE_SIZE));
        $secondMessage = $channel2->receive();
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
