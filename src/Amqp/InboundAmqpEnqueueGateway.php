<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpConsumer;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpSubscriptionConsumer;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use SimplyCodedSoftware\Messaging\Endpoint\MessageDrivenChannelAdapter\MessageDrivenChannelAdapter;

/**
 * Class InboundEnqueueGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundAmqpEnqueueGateway implements MessageDrivenChannelAdapter
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
    /**
     * @var InboundAmqpGateway
     */
    private $inboundAmqpGateway;
    /**
     * @var bool
     */
    private $declareOnStartup;
    /**
     * @var AmqpAdmin
     */
    private $amqpAdmin;
    /**
     * @var string
     */
    private $amqpQueueName;
    /**
     * @var int
     */
    private $receiveTimeoutInMilliseconds;
    /**
     * @var string
     */
    private $acknowledgeMode;

    /**
     * InboundAmqpEnqueueGateway constructor.
     * @param AmqpConnectionFactory $amqpConnectionFactory
     * @param InboundAmqpGateway $inboundAmqpGateway
     * @param AmqpAdmin $amqpAdmin
     * @param bool $declareOnStartup
     * @param string $amqpQueueName
     * @param int $receiveTimeoutInMilliseconds
     * @param string $acknowledgeMode
     */
    public function __construct(
        AmqpConnectionFactory $amqpConnectionFactory,
        InboundAmqpGateway $inboundAmqpGateway,
        AmqpAdmin $amqpAdmin,
        bool $declareOnStartup,
        string $amqpQueueName,
        int $receiveTimeoutInMilliseconds,
        string $acknowledgeMode
    )
    {
        $this->amqpConnectionFactory = $amqpConnectionFactory;
        $this->inboundAmqpGateway = $inboundAmqpGateway;
        $this->declareOnStartup = $declareOnStartup;
        $this->amqpAdmin = $amqpAdmin;
        $this->amqpQueueName = $amqpQueueName;
        $this->receiveTimeoutInMilliseconds = $receiveTimeoutInMilliseconds;
        $this->acknowledgeMode = $acknowledgeMode;
    }

    /**
     * @throws \Interop\Queue\Exception\Exception
     * @throws \Interop\Queue\Exception\SubscriptionConsumerNotSupportedException
     * @throws \SimplyCodedSoftware\Messaging\Support\InvalidArgumentException
     */
    public function startMessageDrivenConsumer(): void
    {
        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();
        $this->amqpAdmin->declareQueueWithBindings($this->amqpQueueName, $context);

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));
//        $consumer->addFlag(AmqpConsumer::)

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, function(AmqpMessage $message, \Interop\Queue\Consumer $consumer) {
            $this->inboundAmqpGateway->execute($message, $consumer);
            $consumer->acknowledge($message);
        });

        $subscriptionConsumer->consume($this->receiveTimeoutInMilliseconds);
    }
}