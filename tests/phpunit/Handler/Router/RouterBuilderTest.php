<?php

namespace Messaging\Handler\Router;

use Fixture\Handler\DumbChannelResolver;
use Fixture\Router\SingleChannelRouter;
use Messaging\Channel\DirectChannel;
use Messaging\Channel\QueueChannel;
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
        $directChannel = DirectChannel::create();
        $chanelName = 'buyChannel';
        $targetChannel = QueueChannel::create();

        $router = RouterBuilder::create('test', $directChannel, SingleChannelRouter::createWithChosenChannelName($chanelName), 'pick')
                    ->setChannelResolver(DumbChannelResolver::create([
                        $chanelName => $targetChannel
                    ]))
                    ->build();

        $message = MessageBuilder::withPayload('some')
            ->build();

        $router->handle($message);

        $this->assertMessages($message, $targetChannel->receive());
    }
}