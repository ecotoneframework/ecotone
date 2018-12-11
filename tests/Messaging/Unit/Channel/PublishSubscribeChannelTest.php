<?php

namespace Test\SimplyCodedSoftware\Messaging\Unit\Channel;

use Test\SimplyCodedSoftware\Messaging\Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Channel\Dispatcher\BroadcastingDispatcher;
use SimplyCodedSoftware\Messaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\Messaging\Channel\PublishSubscribeChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class DirectChannelTest
 * @package SimplyCodedSoftware\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PublishSubscribeChannelTest extends TestCase
{
    public function test_publishing_message()
    {
        $directChannel = new PublishSubscribeChannel(BroadcastingDispatcher::create());

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);

        $directChannel->send(MessageBuilder::withPayload('test')->build());

        $this->assertTrue($messageHandler->wasCalled(), 'Message handler for direct channel was not called');
    }
}