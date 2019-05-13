<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Amqp;

use Enqueue\AmqpLib\AmqpConsumer;
use Interop\Amqp\AmqpConnectionFactory;
use Interop\Amqp\AmqpContext;
use SimplyCodedSoftware\Messaging\Scheduling\TaskExecutor;
use SimplyCodedSoftware\Messaging\Support\InvalidArgumentException;
use Throwable;

/**
 * Class InboundEnqueueGateway
 * @package SimplyCodedSoftware\Amqp
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class AmqpInboundChannelAdapter implements TaskExecutor
{
    /**
     * @var AmqpConnectionFactory
     */
    private $amqpConnectionFactory;
    /**
     * @var AmqpInboundChannelAdapterEntrypoint
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
     * @param AmqpInboundChannelAdapterEntrypoint $inboundAmqpGateway
     * @param AmqpAdmin $amqpAdmin
     * @param bool $declareOnStartup
     * @param string $amqpQueueName
     * @param int $receiveTimeoutInMilliseconds
     * @param string $acknowledgeMode
     */
    public function __construct(
        AmqpConnectionFactory $amqpConnectionFactory,
        AmqpInboundChannelAdapterEntrypoint $inboundAmqpGateway,
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
     * @var bool
     */
    private $initialized = false;
    /**
     * @var AmqpConsumer
     */
    private $initializedConsumer;

    /**
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function execute(): void
    {
        if (!$this->initialized) {
            $context = $this->amqpConnectionFactory->createContext();
            $this->amqpAdmin->declareQueueWithBindings($this->amqpQueueName, $context);
            $this->initialized = true;
        }


        $consumer = $this->getConsumer();
        $amqpMessage = $consumer->receive($this->receiveTimeoutInMilliseconds);

        if (!$amqpMessage) {
            return;
        }

        try {
            $this->inboundAmqpGateway->execute($amqpMessage, $consumer);
            $consumer->acknowledge($amqpMessage);
        } catch (Throwable $e) {
            $consumer->reject($amqpMessage, true);
            throw $e;
        }
    }

    /**
     * @return \Interop\Amqp\AmqpConsumer
     */
    private function getConsumer() : \Interop\Amqp\AmqpConsumer
    {
        if ($this->initializedConsumer) {
            return $this->initializedConsumer;
        }

        /** @var AmqpContext $context */
        $context = $this->amqpConnectionFactory->createContext();

        $consumer = $context->createConsumer(new \Interop\Amqp\Impl\AmqpQueue($this->amqpQueueName));
        $this->initializedConsumer = $consumer;

        return $consumer;
    }
}