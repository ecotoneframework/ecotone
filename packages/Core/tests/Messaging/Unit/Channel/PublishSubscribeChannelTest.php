<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use Ecotone\Messaging\Channel\MessageDispatchingException;
use Ecotone\Messaging\Channel\PublishSubscribeChannel;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class DirectChannelTest
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PublishSubscribeChannelTest extends TestCase
{
    public function test_publishing_message()
    {
        $directChannel = PublishSubscribeChannel::create();

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandler->wasCalled(), 'Message handler for direct channel was not called');
    }
}