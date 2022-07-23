<?php

namespace Test\Ecotone\Messaging\Unit\Channel;

use Ecotone\Messaging\Channel\PublishSubscribeChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\NoReturnMessageHandler;

/**
 * Class DirectChannelTest
 * @package Ecotone\Messaging\Channel
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 *
 * @internal
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
