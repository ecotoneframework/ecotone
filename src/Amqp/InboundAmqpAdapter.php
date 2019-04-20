<?php

namespace SimplyCodedSoftware\Amqp;

use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Queue\Consumer;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerInterceptor;
use SimplyCodedSoftware\Messaging\Endpoint\ConsumerLifecycle;
use Throwable;

/**
 * Class InboundAmqpAdapter
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InboundAmqpAdapter implements ConsumerLifecycle
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
     * @var bool
     */
    private $keepRunning = true;
    /**
     * @var string
     */
    private $endpointId;
    /**
     * @var ConsumerInterceptor[]
     */
    private $consumerExtensions = [];

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
     * @inheritDoc
     */
    public function run(): void
    {
        $this->keepRunning = true;

        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();
        $this->amqpAdmin->declareQueueWithBindings($this->amqpQueueName, $context);

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));

        $subscriptionConsumer = $context->createSubscriptionConsumer();
        $subscriptionConsumer->subscribe($consumer, function (AmqpMessage $message, Consumer $consumer) {
            try {
                $this->inboundAmqpGateway->execute($message, $consumer);
                $consumer->acknowledge($message);
            } catch (Throwable $e) {
                $consumer->reject($message, true);
                throw $e;
            }
        });

        $subscriptionConsumer->consume($this->receiveTimeoutInMilliseconds);
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->keepRunning = false;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->endpointId;
    }
}