<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\DirectChannel;
use Ecotone\Messaging\Channel\Dispatcher\UnicastingDispatcher;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class DirectChannelTest
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class DirectChannelTest extends TestCase
{
    public function test_publishing_message()
    {
        $directChannel = DirectChannel::create();

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandler->wasCalled(), 'Message handler for direct channel was not called');
    }

    public function test_throwing_exception_if_no_handler_exists_for_dispatch()
    {
        $directChannel = DirectChannel::create();

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);
        $directChannel->unsubscribe($messageHandler);

        $this->expectException(MessageDispatchingException::class);

        $directChannel->send(MessageBuilder::withPayload('some')->build());
    }
}