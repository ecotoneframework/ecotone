<?php

namespace Test\SimplyCodedSoftware\Messaging\Channel;

use Fixture\Handler\NoReturnMessageHandler;
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

    public function test_throwing_exception_if_no_handler_exists_for_dispatch()
    {
        $directChannel = new PublishSubscribeChannel(BroadcastingDispatcher::create());

        $messageHandler = NoReturnMessageHandler::create();
        $directChannel->subscribe($messageHandler);
        $directChannel->unsubscribe($messageHandler);

        $this->expectException(MessageDispatchingException::class);

        $directChannel->send(MessageBuilder::withPayload('some')->build());
    }
}