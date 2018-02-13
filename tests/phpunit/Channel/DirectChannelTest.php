<?php

namespace Test\SimplyCodedSoftware\IntegrationMessaging\Channel;

use Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\IntegrationMessaging\Channel\DirectChannel;
use SimplyCodedSoftware\IntegrationMessaging\Channel\Dispatcher\UnicastingDispatcher;
use SimplyCodedSoftware\IntegrationMessaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\IntegrationMessaging\Support\MessageBuilder;

/**
 * Class DirectChannelTest
 * @package SimplyCodedSoftware\IntegrationMessaging\Channel
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