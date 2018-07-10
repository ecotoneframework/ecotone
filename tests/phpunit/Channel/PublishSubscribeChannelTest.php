<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Channel;

use Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\Dispatcher\BroadcastingDispatcher;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\IntegrationMessaging\Channel\PublishSubscribeChannel;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class DirectChannelTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
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