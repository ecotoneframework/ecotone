<?php

namespace Test\SimplyCodedSoftware\Messaging\Handler\Router;

use Fixture\Router\MultipleChannelRouter;
use Fixture\Router\Order;
use Fixture\Router\SingleChannelRouter;
use SimplyCodedSoftware\Messaging\Channel\DirectChannel;
use SimplyCodedSoftware\Messaging\Channel\QueueChannel;
use SimplyCodedSoftware\Messaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\Messaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\Messaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\Messaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\Messaging\MessagingTest;

/**
 * Class RouterBuilderTest
 * @package SimplyCodedSoftware\Messaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilderTest extends MessagingTest
{
    public function test_routing_message_to_single_channel()
    {
        $chanelName = 'buyChannel';
        $targetChannel = QueueChannel::create();
        $inputChannelName = "input";
        $objectToInvokeReference = "service-a";

        $router = RouterBuilder::create('test', $inputChannelName, $objectToInvokeReference, 'pick')
                    ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                        $chanelName => $targetChannel,
                        $inputChannelName => DirectChannel::create()
                    ]))
                    ->setReferenceSearchService(InMemoryReferenceSearchService::createWith([
                        $objectToInvokeReference => SingleChannelRouter::createWithChosenChannelName($chanelName)
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

        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create('test', $inputChannelName, $objectToInvokeReference, 'pick')
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                'channel1' => $targetChannel1,
                'channel2' => $targetChannel2,
                $inputChannelName => DirectChannel::create()
            ]))
            ->setReferenceSearchService(InMemoryReferenceSearchService::createWith([
                $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([
                    'channel1',
                    'channel2'
                ])
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
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create('test', $inputChannelName, $objectToInvokeReference, 'pick')
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]))
            ->setReferenceSearchService(InMemoryReferenceSearchService::createWith([
                $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
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
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create('test', $inputChannelName, $objectToInvokeReference, 'pick')
            ->setResolutionRequired(false)
            ->setChannelResolver(InMemoryChannelResolver::createFromAssociativeArray([
                $inputChannelName => DirectChannel::create()
            ]))
            ->setReferenceSearchService(InMemoryReferenceSearchService::createWith([
                $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
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