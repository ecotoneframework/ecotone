<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Handler\Router;

use Fixture\Router\MultipleChannelRouter;
use Fixture\Router\Order;
use Fixture\Router\SingleChannelRouter;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\QueueChannel;
use SimplyCodedSoftware\IntegrationMessaging\Config\InMemoryChannelResolver;
use SimplyCodedSoftware\IntegrationMessaging\Handler\DestinationResolutionException;
use SimplyCodedSoftware\IntegrationMessaging\Handler\InMemoryReferenceSearchService;
use SimplyCodedSoftware\IntegrationMessaging\Handler\Router\RouterBuilder;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;
use Test\SimplyCodedSoftware\IntegrationMessaging\MessagingTest;

/**
 * Class RouterBuilderTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Handler\Router
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RouterBuilderTest extends MessagingTest
{
    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_message_to_single_channel()
    {
        $chanelName = 'buyChannel';
        $targetChannel = QueueChannel::create();
        $inputChannelName = "input";
        $objectToInvokeReference = "service-a";

        $router = RouterBuilder::create( $inputChannelName, $objectToInvokeReference, 'pick')
                    ->build(
                        InMemoryChannelResolver::createFromAssociativeArray([
                            $chanelName => $targetChannel,
                            $inputChannelName => DirectChannel::create()
                        ]),
                        InMemoryReferenceSearchService::createWith([
                            $objectToInvokeReference => SingleChannelRouter::createWithChosenChannelName($chanelName)
                        ])
                    );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_message_to_multiple_channels()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";

        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create($inputChannelName, $objectToInvokeReference, 'pick')
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2,
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([
                        'channel1',
                        'channel2'
                    ])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
        $this->assertMessages($message, $targetChannel2->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_throwing_exception_if_resolution_is_required()
    {
        $inputChannelName = "input";
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create($inputChannelName, $objectToInvokeReference, 'pick')
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $this->expectException(DestinationResolutionException::class);

        $router->handle($message);
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_if_no_resolution_required_not_throwing_exception_when_no_resolution()
    {
        $inputChannelName = "input";
        $objectToInvokeReference = "service-a";
        $router = RouterBuilder::create( $inputChannelName, $objectToInvokeReference, 'pick')
            ->setResolutionRequired(false)
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createWith([
                    $objectToInvokeReference => MultipleChannelRouter::createWithChosenChannelName([])
                ])
            );

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertTrue(true);
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_with_payload_type()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";

        $router = RouterBuilder::createPayloadTypeRouter($inputChannelName, [
            \stdClass::class => 'channel1',
            Order::class => 'channel2'
        ])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2,
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload(new \stdClass())
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }

    /**
     * @throws \Exception
     * @throws \SimplyCodedSoftware\IntegrationMessaging\MessagingException
     */
    public function test_routing_with_header_value()
    {
        $targetChannel1 = QueueChannel::create();
        $targetChannel2 = QueueChannel::create();
        $inputChannelName = "input";
        $headerName = 'type';

        $router = RouterBuilder::createHeaderValueRouter($inputChannelName, $headerName, [
            'private' => 'channel1',
            'public' => 'channel2'
        ])
            ->build(
                InMemoryChannelResolver::createFromAssociativeArray([
                    'channel1' => $targetChannel1,
                    'channel2' => $targetChannel2,
                    $inputChannelName => DirectChannel::create()
                ]),
                InMemoryReferenceSearchService::createEmpty()
            );

        $message = MessageBuilder::withPayload('some')
                    ->setHeader($headerName, 'private')
                    ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel1->receive());
    }
}