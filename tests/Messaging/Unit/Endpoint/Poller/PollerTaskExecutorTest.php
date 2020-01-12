<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Endpoint\Poller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ecotone\Messaging\Channel\QueueChannel;
use Ecotone\Messaging\Endpoint\EntrypointGateway;
use Ecotone\Messaging\Endpoint\NullAcknowledgementCallback;
use Ecotone\Messaging\Endpoint\NullConsumerLifecycle;
use Ecotone\Messaging\Endpoint\PollingConsumer\PollerTaskExecutor;
use Ecotone\Messaging\Endpoint\StoppableConsumer;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\InvalidArgumentException;
use Ecotone\Messaging\Support\MessageBuilder;

/**
 * Class PollerTaskExecutorTest
 * @package Test\Ecotone\Messaging\Unit\Endpoint\Poller
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class PollerTaskExecutorTest extends TestCase
{
    public function test_passing_message_to_gateway()
    {
        $message = MessageBuilder::withPayload("some")->build();

        $gateway = $this->createMock(EntrypointGateway::class);
        $gateway
            ->expects($this->once())
            ->method("execute")
            ->withAnyParameters();

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();
    }

    /**
     * @param QueueChannel $pollableChannel
     * @param MockObject $gateway
     * @return PollerTaskExecutor
     */
    private function createPoller(QueueChannel $pollableChannel, MockObject $gateway): PollerTaskExecutor
    {
        return new PollerTaskExecutor("", "", $pollableChannel, $gateway);
    }

    public function test_acking_message_when_ack_available_in_message_header()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload("some")
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, "amqpAcker")
            ->setHeader("amqpAcker", $acknowledgementCallback)
            ->build();

        $gateway = $this->createMock(EntrypointGateway::class);

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();

        $this->assertTrue($acknowledgementCallback->isAcked());
    }

    public function test_requeing_message_on_gateway_failure()
    {
        $acknowledgementCallback = NullAcknowledgementCallback::create();
        $message = MessageBuilder::withPayload("some")
            ->setHeader(MessageHeaders::CONSUMER_ACK_HEADER_LOCATION, "amqpAcker")
            ->setHeader("amqpAcker", $acknowledgementCallback)
            ->build();

        $gateway = $this->createMock(EntrypointGateway::class);
        $gateway
            ->expects($this->once())
            ->method("execute")
            ->willThrowException(InvalidArgumentException::create("gateway test exception"));

        $pollableChannel = QueueChannel::create();
        $pollableChannel->send($message);

        $pollingExecutor = $this->createPoller($pollableChannel, $gateway);
        $pollingExecutor->execute();

        $this->assertTrue($acknowledgementCallback->isRequeued());
    }
}