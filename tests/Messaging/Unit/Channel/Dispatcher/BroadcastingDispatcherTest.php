<?php

namespace Test\Ecotone\Messaging\Unit\Channel\Dispatcher;

use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class BroadcastingDispatcherTest
 * @package Ecotone\Messaging\Channel\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class BroadcastingDispatcherTest extends TestCase
{
    public function test_broadcasting_to_multiple_handlers()
    {
        $broadcastingDispatcher = new BroadcastingDispatcher();

        $messageHandlerOne = NoReturnMessageHandler::create();
        $messageHandlerTwo = NoReturnMessageHandler::create();
        $broadcastingDispatcher->addHandler($messageHandlerOne);
        $broadcastingDispatcher->addHandler($messageHandlerTwo);

        $broadcastingDispatcher->dispatch(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandlerOne->wasCalled(), "Message handler was no called");
        $this->assertTrue($messageHandlerTwo->wasCalled(), "Message handler was no called");
    }

    public function test_counting_handlers_amount()
    {
        $broadcastingDispatcher = new BroadcastingDispatcher();

        $this->assertEquals(0, $broadcastingDispatcher->getHandlerCount());

        $broadcastingDispatcher->addHandler(NoReturnMessageHandler::create());
        $this->assertEquals(1, $broadcastingDispatcher->getHandlerCount());
    }

    public function test_removing_handler()
    {
        $broadcastingDispatcher = new BroadcastingDispatcher();

        $messageHandler = NoReturnMessageHandler::create();
        $broadcastingDispatcher->addHandler($messageHandler);
        $broadcastingDispatcher->addHandler(NoReturnMessageHandler::create());

        $broadcastingDispatcher->removeHandler($messageHandler);
        $this->assertEquals(1, $broadcastingDispatcher->getHandlerCount());
    }
}