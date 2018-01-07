<?php

namespace Messaging\Handler\Router;

use Fixture\Handler\DumbChannelResolver;
use Fixture\Router\MultipleChannelRouter;
use Fixture\Router\Order;
use Fixture\Router\SingleChannelRouter;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
use Messaging\Config\InMemoryChannelResolver;
use Messaging\Handler\DestinationResolutionException;
use Messaging\MessagingTest;
use Messaging\Support\MessageBuilder;

/**
 * Class RouterBuilderTest
 * @package Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilderTest extends MessagingTest
{
    public function test_routing_message_to_single_channel()
    {
        $chanelName = 'buyChannel';
        $targetChannel = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::create('test', $inputChannelName, SingleChannelRouter::createWithChosenChannelName($chanelName), 'pick')
                    ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                        $chanelName => $targetChannel,
                        $inputChannelName => DirectChannel::create()
                    ]))
                    ->build();

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    public function test_routing_message_to_multiple_channels()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::create('test', $inputChannelName, MultipleChannelRouter::createWithChosenChannelName([
            'channel1',
            'channel2'
        ]), 'pick')
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                'channel1' => $targetChannel1,
                'channel2' => $targetChannel2,
                $inputChannelName => DirectChannel::create()
            ]))
            ->build();

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
        $this->assertMessages($message, $targetChannel2->receive());
    }

    public function test_throwing_exception_if_resolution_is_required()
    {
        $inputChannelName = "input";
        $router = RouterBuilder::create('test', $inputChannelName, MultipleChannelRouter::createWithChosenChannelName([]), 'pick')
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]))
            ->build();

        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->expectException(DestinationResolutionException::class);

        $router->handle($message);
    }

    public function test_if_no_resolution_required_not_throwing_exception_when_no_resolution()
    {
        $inputChannelName = "input";
        $router = RouterBuilder::create('test', $inputChannelName, MultipleChannelRouter::createWithChosenChannelName([]), 'pick')
            ->setResolutionRequired(false)
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]))
            ->build();

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertTrue(true);
    }

    public function test_routing_with_payload_type()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::createPayloadTypeRouter('test', $inputChannelName, [
            \stdClass::class => 'channel1',
            Order::class => 'channel2'
        ])
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                'channel1' => $targetChannel1,
                'channel2' => $targetChannel2,
                $inputChannelName => DirectChannel::create()
            ]))
            ->build();

        $message = MessageBuilder::withPayload(new \stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    public function test_routing_with_header_value()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";
        $headerName = 'type';

        $router = RouterBuilder::createHeaderValueRouter('test', $inputChannelName, $headerName, [
            'private' => 'channel1',
            'public' => 'channel2'
        ])
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                'channel1' => $targetChannel1,
                'channel2' => $targetChannel2,
                $inputChannelName => DirectChannel::create()
            ]))
            ->build();

        $message = MessageBuilder::withPayload('some')
                    ->setHeader($headerName, 'private')
                    ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }
}