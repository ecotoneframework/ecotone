<?php
declare(strict_types=1);


namespace SimplyCodedSoftware\Amqp;

use Enqueue\AmqpLib\AmqpConsumer;
use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\AmqpConnectionFactory;
use SimplyCodedSoftware\Messaging\Message;
use SimplyCodedSoftware\Messaging\PollableChannel;
use SimplyCodedSoftware\Messaging\Support\MessageBuilder;

/**
 * Class AmqpChannel
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpChannel implements PollableChannel
{
    /**
     * @var string
     */
    private $amqpQueueName;
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;


    /**
     * @var AmqpConsumer
     */
    private $consumer;
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * AmqpChannel constructor.
     * @param string $amqpQueueName
     * @param AmqpConnectionFactory $amqpConnectionFactory
     */
    public function __construct(string $amqpQueueName, AmqpConnectionFactory $amqpConnectionFactory)
    {
        $this->amqpQueueName = $amqpQueueName;
        $this->amqpConnectionFactory = $amqpConnectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function send(Message $message): void
    {
        // TODO: Implement send() method.
    }

    /**
     * @inheritDoc
     */
    public function receive(): ?Message
    {
        return $this->getAmqpMessage(1);
    }

    /**
     * @param int $timeout
     * @return Message|null
     */
    private function getAmqpMessage(int $timeout)
    {
        $amqpMessage = $this->getConsumer()->receive($timeout * 1000);

        if (!$amqpMessage) {
            return null;
        }

        $amqpAcknowledgementCallback = AmqpAcknowledgementCallback::createWithAutoAck($this->getConsumer(), $amqpMessage);

        return MessageBuilder::withPayload($amqpMessage)
            ->setHeader(AmqpHeader::HEADER_ACKNOWLEDGE, $amqpAcknowledgementCallback)
            ->setHeader(AmqpHeader::HEADER_RELATED_AMQP_LIB_CHANNEL, $this->context->getLibChannel())
            ->build();
    }

    /**
     * @return AmqpConsumer
     */
    private function getConsumer(): AmqpConsumer
    {
        if (!$this->consumer) {
            $this->context = $this->amqpConnectionFactory->createContext();
            $this->consumer = $this->context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));
        }

        return $this->consumer;
    }

    /**
     * @inheritDoc
     */
    public function receiveWithTimeout(int $timeoutInMilliseconds): ?Message
    {
        return $this->getAmqpMessage($timeoutInMilliseconds);
    }
}