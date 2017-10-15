<?php

namespace Messaging\Channel;

use Fixture\Handler\NoReturnMessageHandler;
use Messaging\Channel\Dispatcher\UnicastingDispatcher;
use Messaging\Message;
use Messaging\MessageHandler;
use Messaging\SubscribableChannel;
use Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Class DirectChannelTest
 * @package Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DirectChannelTest extends TestCase
{
    public function test_publishing_message()
    {
        $directChannel = new DirectChannel(UnicastingDispatcher::create());

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandler->wasCalled(), 'Message handler for direct channel was not called');
    }

    public function test_throwing_exception_if_no_handler_exists_for_dispatch()
    {
        $directChannel = new DirectChannel(UnicastingDispatcher::create());

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);
        $directChannel->unsubscribe($messageHandler);

        $this->expectException(MessageDispatchingException::class);

        $directChannel->send(MessageBuilder::withPayload('some')->build());
    }
}