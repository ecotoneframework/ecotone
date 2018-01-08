<?php

namespace Test\SimplyCodedSoftware\Messaging\Channel\Dispatcher;

use Fixture\Handler\NoReturnMessageHandler;
use PHPUnit\Framework\TestCase;
use SimplyCodedSoftware\Messaging\Channel\Dispatcher\UnicastingDispatcher;
use SimplyCodedSoftware\Messaging\Channel\MessageDispatchingException;
use SimplyCodedSoftware\Messaging\Channel\WrongHandlerAmountException;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class UnicastingDispatcherTest
 * @package SimplyCodedSoftware\Messaging\Dispatcher
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class UnicastingDispatcherTest extends TestCase
{
    public function test_sending_message()
    {
        $messageHandler = NoReturnMessageHandler::create();

        $unicastingDispatcher = new UnicastingDispatcher();
        $unicastingDispatcher->addHandler($messageHandler);

        $unicastingDispatcher->dispatch(
            MessageBuilder::withPayload('some')
                ->build()
        );

        $this->assertTrue($messageHandler->wasCalled(), "Handler was not called");
    }

    public function test_counting_message_handler()
    {
        $unicastingDispatcher = new UnicastingDispatcher();

        $this->assertEquals(0, $unicastingDispatcher->getHandlerCount());

        $unicastingDispatcher->addHandler(NoReturnMessageHandler::create());

        $this->assertEquals(1, $unicastingDispatcher->getHandlerCount());
    }

    public function test_throwing_exception_if_adding_more_then_one_handler()
    {
        $unicastingDispatcher = new UnicastingDispatcher();

        $this->expectException(WrongHandlerAmountException::class);

        $unicastingDispatcher->addHandler(NoReturnMessageHandler::create());
        $unicastingDispatcher->addHandler(NoReturnMessageHandler::create());
    }

    public function test_throwing_exception_when_dispatching_without_handler_registered()
    {
        $unicastingDispatcher = new UnicastingDispatcher();

        $this->expectException(MessageDispatchingException::class);

        $unicastingDispatcher->dispatch(MessageBuilder::withPayload('a')->build());
    }
}